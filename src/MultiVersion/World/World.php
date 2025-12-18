<?php
declare(strict_types=1);

namespace MultiVersion\World;

use MultiVersion\MultiVersion;

final class World {

    private MultiVersion $plugin;
    private string $name;
    private ChunkManager $chunkManager;
    private BlockRegistry $blockRegistry;
    private int $seed;
    private int $time = 0;
    private int $difficulty = 1;
    private array $spawnLocation = ['x' => 0, 'y' => 64, 'z' => 0];
    private array $worldData = [];
    private bool $autoSave = true;
    private int $autoSaveInterval = 6000;
    private int $lastAutoSave = 0;

    public function __construct(MultiVersion $plugin, string $name, int $seed = 0) {
        $this->plugin = $plugin;
        $this->name = $name;
        $this->seed = $seed === 0 ? random_int(PHP_INT_MIN, PHP_INT_MAX) : $seed;
        $this->chunkManager = new ChunkManager($plugin);
        $this->blockRegistry = new BlockRegistry($plugin);
        $this->loadWorldData();
    }

    private function loadWorldData(): void {
        $dataFile = $this->getWorldDataFile();

        if (file_exists($dataFile)) {
            $json = file_get_contents($dataFile);
            $data = json_decode($json, true);

            if ($data !== null) {
                $this->worldData = $data;
                $this->time = $data['time'] ?? 0;
                $this->difficulty = $data['difficulty'] ?? 1;
                $this->spawnLocation = $data['spawnLocation'] ?? $this->spawnLocation;
                $this->plugin->getMVLogger()->info("Loaded world data for '{$this->name}'");
            }
        } else {
            $this->plugin->getMVLogger()->info("Creating new world '{$this->name}'");
            $this->saveWorldData();
        }
    }

    public function saveWorldData(): bool {
        $this->worldData = [
            'name' => $this->name,
            'seed' => $this->seed,
            'time' => $this->time,
            'difficulty' => $this->difficulty,
            'spawnLocation' => $this->spawnLocation,
            'lastSaved' => time()
        ];

        $dataFile = $this->getWorldDataFile();
        $dataDir = dirname($dataFile);

        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0777, true);
        }

        $json = json_encode($this->worldData, JSON_PRETTY_PRINT);
        $result = file_put_contents($dataFile, $json, LOCK_EX);

        if ($result !== false) {
            $this->plugin->getMVLogger()->debug("Saved world data for '{$this->name}'");
            return true;
        }

        return false;
    }

    private function getWorldDataFile(): string {
        return $this->plugin->getDataFolder() . "worlds/{$this->name}/level.json";
    }

    public function getName(): string {
        return $this->name;
    }

    public function getSeed(): int {
        return $this->seed;
    }

    public function getTime(): int {
        return $this->time;
    }

    public function setTime(int $time): void {
        $this->time = $time % 24000;
    }

    public function addTime(int $ticks): void {
        $this->time = ($this->time + $ticks) % 24000;
    }

    public function getDifficulty(): int {
        return $this->difficulty;
    }

    public function setDifficulty(int $difficulty): void {
        $this->difficulty = max(0, min(3, $difficulty));
    }

    public function getSpawnLocation(): array {
        return $this->spawnLocation;
    }

    public function setSpawnLocation(int $x, int $y, int $z): void {
        $this->spawnLocation = ['x' => $x, 'y' => $y, 'z' => $z];
    }

    public function getBlock(int $x, int $y, int $z): Block {
        $chunkX = $x >> 4;
        $chunkZ = $z >> 4;

        $chunk = $this->chunkManager->getChunk($chunkX, $chunkZ);

        if ($chunk === null) {
            $chunk = $this->chunkManager->loadChunk($chunkX, $chunkZ, true);
        }

        if ($chunk === null) {
            return Block::air();
        }

        return $chunk->getBlock($x & 0x0f, $y, $z & 0x0f);
    }

    public function setBlock(int $x, int $y, int $z, Block $block): void {
        $chunkX = $x >> 4;
        $chunkZ = $z >> 4;

        $chunk = $this->chunkManager->getChunk($chunkX, $chunkZ);

        if ($chunk === null) {
            $chunk = $this->chunkManager->loadChunk($chunkX, $chunkZ, true);
        }

        if ($chunk === null) {
            return;
        }

        $chunk->setBlock($x & 0x0f, $y, $z & 0x0f, $block);
    }

    public function getChunk(int $x, int $z): ?Chunk {
        return $this->chunkManager->getChunk($x, $z);
    }

    public function loadChunk(int $x, int $z): ?Chunk {
        return $this->chunkManager->loadChunk($x, $z, true);
    }

    public function unloadChunk(int $x, int $z, bool $save = true): bool {
        return $this->chunkManager->unloadChunk($x, $z, $save);
    }

    public function isChunkLoaded(int $x, int $z): bool {
        return $this->chunkManager->isChunkLoaded($x, $z);
    }

    public function getChunkManager(): ChunkManager {
        return $this->chunkManager;
    }

    public function getBlockRegistry(): BlockRegistry {
        return $this->blockRegistry;
    }

    public function tick(): void {
        $this->addTime(1);

        if ($this->autoSave) {
            $this->lastAutoSave++;
            if ($this->lastAutoSave >= $this->autoSaveInterval) {
                $this->save();
                $this->lastAutoSave = 0;
            }
        }

        $this->chunkManager->processLoadQueue(5);
        $this->chunkManager->processSaveQueue(10);
    }

    public function save(): void {
        $saved = $this->chunkManager->saveAllChunks();
        $this->saveWorldData();
        $this->plugin->getMVLogger()->info("Saved world '{$this->name}' ({$saved} chunks)");
    }

    public function unload(bool $save = true): void {
        if ($save) {
            $this->save();
        }

        $this->chunkManager->unloadAllChunks($save);
        $this->plugin->getMVLogger()->info("Unloaded world '{$this->name}'");
    }

    public function setAutoSave(bool $enabled): void {
        $this->autoSave = $enabled;
    }

    public function isAutoSaveEnabled(): bool {
        return $this->autoSave;
    }

    public function setAutoSaveInterval(int $ticks): void {
        $this->autoSaveInterval = max(1200, $ticks);
    }

    public function getAutoSaveInterval(): int {
        return $this->autoSaveInterval;
    }

    public function getLoadedChunks(): array {
        return $this->chunkManager->getLoadedChunks();
    }

    public function getLoadedChunkCount(): int {
        return $this->chunkManager->getLoadedChunkCount();
    }

    public function getStatistics(): array {
        return [
            'name' => $this->name,
            'seed' => $this->seed,
            'time' => $this->time,
            'difficulty' => $this->difficulty,
            'auto_save' => $this->autoSave,
            'chunk_stats' => $this->chunkManager->getStatistics()
        ];
    }

    public static function chunkHash(int $x, int $z): int {
        return (($x & 0xFFFFFFFF) << 32) | ($z & 0xFFFFFFFF);
    }

    public static function getXZ(int $hash, &$x, &$z): void {
        $x = $hash >> 32;
        $z = $hash & 0xFFFFFFFF;
    }

    public function __toString(): string {
        return "World(name={$this->name}, seed={$this->seed}, time={$this->time})";
    }
}