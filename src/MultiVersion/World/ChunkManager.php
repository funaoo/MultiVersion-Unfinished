<?php
declare(strict_types=1);

namespace MultiVersion\World;

use MultiVersion\MultiVersion;

final class ChunkManager {

    private MultiVersion $plugin;
    private array $chunks = [];
    private array $chunkLoadQueue = [];
    private array $chunkSaveQueue = [];
    private int $maxLoadedChunks = 1000;
    private int $chunksLoaded = 0;
    private int $chunksSaved = 0;

    public function __construct(MultiVersion $plugin) {
        $this->plugin = $plugin;
    }

    public function loadChunk(int $x, int $z, bool $generate = true): ?Chunk {
        $hash = $this->getChunkHash($x, $z);

        if (isset($this->chunks[$hash])) {
            return $this->chunks[$hash];
        }

        $chunk = $this->loadChunkFromDisk($x, $z);

        if ($chunk === null && $generate) {
            $chunk = $this->generateChunk($x, $z);
        }

        if ($chunk !== null) {
            $this->chunks[$hash] = $chunk;
            $this->chunksLoaded++;
            $this->checkChunkLimit();
        }

        return $chunk;
    }

    private function loadChunkFromDisk(int $x, int $z): ?Chunk {
        $chunkFile = $this->getChunkFile($x, $z);

        if (!file_exists($chunkFile)) {
            return null;
        }

        try {
            $data = file_get_contents($chunkFile);
            if ($data === false) {
                return null;
            }

            $chunk = $this->deserializeChunk($x, $z, $data);
            $this->plugin->getMVLogger()->debug("Loaded chunk ({$x}, {$z}) from disk");
            return $chunk;
        } catch (\Exception $e) {
            $this->plugin->getMVLogger()->error("Failed to load chunk ({$x}, {$z}): {$e->getMessage()}");
            return null;
        }
    }

    private function generateChunk(int $x, int $z): Chunk {
        $chunk = new Chunk($x, $z);

        for ($cx = 0; $cx < 16; $cx++) {
            for ($cz = 0; $cz < 16; $cz++) {
                $chunk->setBlock($cx, 0, $cz, Block::bedrock());

                for ($y = 1; $y < 64; $y++) {
                    $chunk->setBlock($cx, $y, $cz, Block::stone());
                }

                $chunk->setBlock($cx, 64, $cz, Block::dirt());
                $chunk->setBlock($cx, 65, $cz, Block::grass());

                $chunk->setBiome($cx, $cz, 1);
            }
        }

        $chunk->setPopulated(true);
        $this->plugin->getMVLogger()->debug("Generated chunk ({$x}, {$z})");

        return $chunk;
    }

    public function saveChunk(Chunk $chunk): bool {
        if (!$chunk->isDirty()) {
            return true;
        }

        $chunkFile = $this->getChunkFile($chunk->getX(), $chunk->getZ());
        $chunkDir = dirname($chunkFile);

        if (!is_dir($chunkDir)) {
            mkdir($chunkDir, 0777, true);
        }

        try {
            $data = $this->serializeChunk($chunk);
            $result = file_put_contents($chunkFile, $data, LOCK_EX);

            if ($result !== false) {
                $chunk->clearDirty();
                $this->chunksSaved++;
                $this->plugin->getMVLogger()->debug("Saved chunk ({$chunk->getX()}, {$chunk->getZ()}) to disk");
                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->plugin->getMVLogger()->error(
                "Failed to save chunk ({$chunk->getX()}, {$chunk->getZ()}): {$e->getMessage()}"
            );
            return false;
        }
    }

    public function unloadChunk(int $x, int $z, bool $save = true): bool {
        $hash = $this->getChunkHash($x, $z);

        if (!isset($this->chunks[$hash])) {
            return false;
        }

        $chunk = $this->chunks[$hash];

        if ($save && $chunk->isDirty()) {
            $this->saveChunk($chunk);
        }

        unset($this->chunks[$hash]);
        $this->plugin->getMVLogger()->debug("Unloaded chunk ({$x}, {$z})");

        return true;
    }

    public function getChunk(int $x, int $z): ?Chunk {
        $hash = $this->getChunkHash($x, $z);
        return $this->chunks[$hash] ?? null;
    }

    public function isChunkLoaded(int $x, int $z): bool {
        $hash = $this->getChunkHash($x, $z);
        return isset($this->chunks[$hash]);
    }

    public function getLoadedChunks(): array {
        return $this->chunks;
    }

    public function getLoadedChunkCount(): int {
        return count($this->chunks);
    }

    private function getChunkHash(int $x, int $z): string {
        return "{$x}:{$z}";
    }

    private function getChunkFile(int $x, int $z): string {
        $regionX = $x >> 5;
        $regionZ = $z >> 5;

        return $this->plugin->getDataFolder() .
            "worlds/default/region/r.{$regionX}.{$regionZ}/c.{$x}.{$z}.dat";
    }

    private function serializeChunk(Chunk $chunk): string {
        $data = [
            'x' => $chunk->getX(),
            'z' => $chunk->getZ(),
            'subChunks' => [],
            'biomes' => [],
            'heightMap' => [],
            'blockEntities' => $chunk->getAllBlockEntities(),
            'entities' => $chunk->getEntities(),
            'isPopulated' => $chunk->isPopulated(),
            'lastModified' => $chunk->getLastModified()
        ];

        for ($y = 0; $y < Chunk::MAX_SUBCHUNKS; $y++) {
            $subChunk = $chunk->getSubChunk($y);
            if (!empty($subChunk)) {
                $data['subChunks'][$y] = $this->compressSubChunk($subChunk);
            }
        }

        for ($x = 0; $x < 16; $x++) {
            for ($z = 0; $z < 16; $z++) {
                $data['biomes'][] = $chunk->getBiome($x, $z);
                $data['heightMap'][] = $chunk->getHeight($x, $z);
            }
        }

        return gzencode(serialize($data), 6);
    }

    private function deserializeChunk(int $x, int $z, string $data): Chunk {
        $decoded = gzdecode($data);
        if ($decoded === false) {
            throw new \RuntimeException("Failed to decompress chunk data");
        }

        $chunkData = unserialize($decoded);
        if ($chunkData === false) {
            throw new \RuntimeException("Failed to unserialize chunk data");
        }

        $chunk = new Chunk($x, $z);

        foreach ($chunkData['subChunks'] as $y => $compressedSubChunk) {
            $subChunk = $this->decompressSubChunk($compressedSubChunk);
            $chunk->setSubChunk($y, $subChunk);
        }

        $index = 0;
        for ($cx = 0; $cx < 16; $cx++) {
            for ($cz = 0; $cz < 16; $cz++) {
                if (isset($chunkData['biomes'][$index])) {
                    $chunk->setBiome($cx, $cz, $chunkData['biomes'][$index]);
                }
                $index++;
            }
        }

        foreach ($chunkData['blockEntities'] ?? [] as $key => $blockEntity) {
            $parts = explode(':', $key);
            if (count($parts) === 3) {
                $chunk->addBlockEntity((int)$parts[0], (int)$parts[1], (int)$parts[2], $blockEntity);
            }
        }

        foreach ($chunkData['entities'] ?? [] as $entityId => $entity) {
            $chunk->addEntity($entityId, $entity);
        }

        if ($chunkData['isPopulated'] ?? false) {
            $chunk->setPopulated(true);
        }

        $chunk->clearDirty();

        return $chunk;
    }

    private function compressSubChunk(array $subChunk): array {
        $palette = [];
        $paletteMap = [];
        $indices = [];

        foreach ($subChunk as $blockId) {
            if (!isset($paletteMap[$blockId])) {
                $paletteMap[$blockId] = count($palette);
                $palette[] = $blockId;
            }
            $indices[] = $paletteMap[$blockId];
        }

        return [
            'palette' => $palette,
            'indices' => $indices
        ];
    }

    private function decompressSubChunk(array $compressed): array {
        $palette = $compressed['palette'];
        $indices = $compressed['indices'];
        $subChunk = [];

        foreach ($indices as $index) {
            $subChunk[] = $palette[$index];
        }

        return $subChunk;
    }

    public function queueChunkLoad(int $x, int $z): void {
        $hash = $this->getChunkHash($x, $z);
        if (!isset($this->chunkLoadQueue[$hash])) {
            $this->chunkLoadQueue[$hash] = ['x' => $x, 'z' => $z, 'time' => microtime(true)];
        }
    }

    public function queueChunkSave(Chunk $chunk): void {
        $hash = $this->getChunkHash($chunk->getX(), $chunk->getZ());
        $this->chunkSaveQueue[$hash] = $chunk;
    }

    public function processLoadQueue(int $maxChunks = 5): void {
        $processed = 0;

        foreach ($this->chunkLoadQueue as $hash => $data) {
            if ($processed >= $maxChunks) {
                break;
            }

            $this->loadChunk($data['x'], $data['z']);
            unset($this->chunkLoadQueue[$hash]);
            $processed++;
        }
    }

    public function processSaveQueue(int $maxChunks = 10): void {
        $processed = 0;

        foreach ($this->chunkSaveQueue as $hash => $chunk) {
            if ($processed >= $maxChunks) {
                break;
            }

            $this->saveChunk($chunk);
            unset($this->chunkSaveQueue[$hash]);
            $processed++;
        }
    }

    private function checkChunkLimit(): void {
        if (count($this->chunks) > $this->maxLoadedChunks) {
            $this->unloadOldestChunks();
        }
    }

    private function unloadOldestChunks(): void {
        $chunkTimes = [];

        foreach ($this->chunks as $hash => $chunk) {
            $chunkTimes[$hash] = $chunk->getLastModified();
        }

        asort($chunkTimes);
        $toUnload = array_slice(array_keys($chunkTimes), 0, 100);

        foreach ($toUnload as $hash) {
            $parts = explode(':', $hash);
            if (count($parts) === 2) {
                $this->unloadChunk((int)$parts[0], (int)$parts[1], true);
            }
        }
    }

    public function saveAllChunks(): int {
        $saved = 0;

        foreach ($this->chunks as $chunk) {
            if ($chunk->isDirty()) {
                if ($this->saveChunk($chunk)) {
                    $saved++;
                }
            }
        }

        $this->plugin->getMVLogger()->info("Saved {$saved} chunks to disk");
        return $saved;
    }

    public function unloadAllChunks(bool $save = true): void {
        $count = count($this->chunks);

        foreach ($this->chunks as $hash => $chunk) {
            $parts = explode(':', $hash);
            if (count($parts) === 2) {
                $this->unloadChunk((int)$parts[0], (int)$parts[1], $save);
            }
        }

        $this->plugin->getMVLogger()->info("Unloaded {$count} chunks");
    }

    public function setMaxLoadedChunks(int $max): void {
        $this->maxLoadedChunks = max(100, $max);
    }

    public function getMaxLoadedChunks(): int {
        return $this->maxLoadedChunks;
    }

    public function getStatistics(): array {
        return [
            'loaded_chunks' => $this->getLoadedChunkCount(),
            'max_loaded_chunks' => $this->maxLoadedChunks,
            'chunks_loaded' => $this->chunksLoaded,
            'chunks_saved' => $this->chunksSaved,
            'load_queue_size' => count($this->chunkLoadQueue),
            'save_queue_size' => count($this->chunkSaveQueue)
        ];
    }

    public function clearLoadQueue(): void {
        $this->chunkLoadQueue = [];
    }

    public function clearSaveQueue(): void {
        $this->chunkSaveQueue = [];
    }

    public function getChunksInRadius(int $centerX, int $centerZ, int $radius): array {
        $chunks = [];

        for ($x = $centerX - $radius; $x <= $centerX + $radius; $x++) {
            for ($z = $centerZ - $radius; $z <= $centerZ + $radius; $z++) {
                $distance = sqrt(pow($x - $centerX, 2) + pow($z - $centerZ, 2));
                if ($distance <= $radius) {
                    $chunk = $this->getChunk($x, $z);
                    if ($chunk !== null) {
                        $chunks[] = $chunk;
                    }
                }
            }
        }

        return $chunks;
    }
}