<?php
declare(strict_types=1);

namespace MultiVersion\World;

final class Chunk {

    private int $x;
    private int $z;
    private array $subChunks = [];
    private array $biomes = [];
    private array $heightMap = [];
    private array $blockEntities = [];
    private array $entities = [];
    private bool $isDirty = false;
    private bool $isPopulated = false;
    private float $lastModified;

    public const MAX_SUBCHUNKS = 24;
    public const SUBCHUNK_HEIGHT = 16;

    public function __construct(int $x, int $z) {
        $this->x = $x;
        $this->z = $z;
        $this->lastModified = microtime(true);
        $this->initializeChunk();
    }

    private function initializeChunk(): void {
        for ($y = 0; $y < self::MAX_SUBCHUNKS; $y++) {
            $this->subChunks[$y] = $this->createEmptySubChunk();
        }

        for ($x = 0; $x < 16; $x++) {
            for ($z = 0; $z < 16; $z++) {
                $this->biomes[$this->getIndex($x, $z)] = 1;
                $this->heightMap[$this->getIndex($x, $z)] = 0;
            }
        }
    }

    private function createEmptySubChunk(): array {
        $blocks = [];
        for ($i = 0; $i < 4096; $i++) {
            $blocks[$i] = 0;
        }
        return $blocks;
    }

    public function getX(): int {
        return $this->x;
    }

    public function getZ(): int {
        return $this->z;
    }

    public function getBlock(int $x, int $y, int $z): Block {
        if ($x < 0 || $x >= 16 || $z < 0 || $z >= 16) {
            return Block::air();
        }

        if ($y < 0 || $y >= 384) {
            return Block::air();
        }

        $subChunkY = $y >> 4;
        if (!isset($this->subChunks[$subChunkY])) {
            return Block::air();
        }

        $index = $this->getBlockIndex($x, $y & 0x0f, $z);
        $fullId = $this->subChunks[$subChunkY][$index] ?? 0;

        return Block::fromFullId($fullId);
    }

    public function setBlock(int $x, int $y, int $z, Block $block): void {
        if ($x < 0 || $x >= 16 || $z < 0 || $z >= 16) {
            return;
        }

        if ($y < 0 || $y >= 384) {
            return;
        }

        $subChunkY = $y >> 4;
        if (!isset($this->subChunks[$subChunkY])) {
            $this->subChunks[$subChunkY] = $this->createEmptySubChunk();
        }

        $index = $this->getBlockIndex($x, $y & 0x0f, $z);
        $this->subChunks[$subChunkY][$index] = $block->getFullId();

        $this->updateHeightMap($x, $z, $y);
        $this->markDirty();
    }

    private function getBlockIndex(int $x, int $y, int $z): int {
        return ($y << 8) | ($z << 4) | $x;
    }

    private function getIndex(int $x, int $z): int {
        return ($z << 4) | $x;
    }

    public function getBiome(int $x, int $z): int {
        if ($x < 0 || $x >= 16 || $z < 0 || $z >= 16) {
            return 1;
        }

        return $this->biomes[$this->getIndex($x, $z)] ?? 1;
    }

    public function setBiome(int $x, int $z, int $biomeId): void {
        if ($x < 0 || $x >= 16 || $z < 0 || $z >= 16) {
            return;
        }

        $this->biomes[$this->getIndex($x, $z)] = $biomeId;
        $this->markDirty();
    }

    public function getHeight(int $x, int $z): int {
        if ($x < 0 || $x >= 16 || $z < 0 || $z >= 16) {
            return 0;
        }

        return $this->heightMap[$this->getIndex($x, $z)] ?? 0;
    }

    private function updateHeightMap(int $x, int $z, int $y): void {
        $index = $this->getIndex($x, $z);
        $currentHeight = $this->heightMap[$index] ?? 0;

        if ($y > $currentHeight) {
            $this->heightMap[$index] = $y;
        } elseif ($y === $currentHeight) {
            for ($checkY = $y - 1; $checkY >= 0; $checkY--) {
                $block = $this->getBlock($x, $checkY, $z);
                if (!$block->isAir()) {
                    $this->heightMap[$index] = $checkY;
                    break;
                }
            }
        }
    }

    public function getSubChunk(int $y): array {
        return $this->subChunks[$y] ?? [];
    }

    public function setSubChunk(int $y, array $blocks): void {
        if ($y < 0 || $y >= self::MAX_SUBCHUNKS) {
            return;
        }

        $this->subChunks[$y] = $blocks;
        $this->markDirty();
    }

    public function getSubChunkCount(): int {
        return count(array_filter($this->subChunks, fn($sc) => !empty($sc)));
    }

    public function addBlockEntity(int $x, int $y, int $z, array $data): void {
        $key = $this->getBlockEntityKey($x, $y, $z);
        $this->blockEntities[$key] = $data;
        $this->markDirty();
    }

    public function removeBlockEntity(int $x, int $y, int $z): void {
        $key = $this->getBlockEntityKey($x, $y, $z);
        unset($this->blockEntities[$key]);
        $this->markDirty();
    }

    public function getBlockEntity(int $x, int $y, int $z): ?array {
        $key = $this->getBlockEntityKey($x, $y, $z);
        return $this->blockEntities[$key] ?? null;
    }

    private function getBlockEntityKey(int $x, int $y, int $z): string {
        return "{$x}:{$y}:{$z}";
    }

    public function getAllBlockEntities(): array {
        return $this->blockEntities;
    }

    public function addEntity(int $entityId, array $data): void {
        $this->entities[$entityId] = $data;
        $this->markDirty();
    }

    public function removeEntity(int $entityId): void {
        unset($this->entities[$entityId]);
        $this->markDirty();
    }

    public function getEntities(): array {
        return $this->entities;
    }

    public function markDirty(): void {
        $this->isDirty = true;
        $this->lastModified = microtime(true);
    }

    public function isDirty(): bool {
        return $this->isDirty;
    }

    public function clearDirty(): void {
        $this->isDirty = false;
    }

    public function isPopulated(): bool {
        return $this->isPopulated;
    }

    public function setPopulated(bool $populated): void {
        $this->isPopulated = $populated;
        $this->markDirty();
    }

    public function getLastModified(): float {
        return $this->lastModified;
    }

    public function getHeight(): int {
        return self::MAX_SUBCHUNKS * self::SUBCHUNK_HEIGHT;
    }

    public function fill(Block $block): void {
        $fullId = $block->getFullId();

        for ($y = 0; $y < self::MAX_SUBCHUNKS; $y++) {
            $subChunk = [];
            for ($i = 0; $i < 4096; $i++) {
                $subChunk[$i] = $fullId;
            }
            $this->subChunks[$y] = $subChunk;
        }

        $this->markDirty();
    }

    public function clone(): self {
        $cloned = new self($this->x, $this->z);
        $cloned->subChunks = $this->subChunks;
        $cloned->biomes = $this->biomes;
        $cloned->heightMap = $this->heightMap;
        $cloned->blockEntities = $this->blockEntities;
        $cloned->entities = $this->entities;
        $cloned->isPopulated = $this->isPopulated;
        return $cloned;
    }
}