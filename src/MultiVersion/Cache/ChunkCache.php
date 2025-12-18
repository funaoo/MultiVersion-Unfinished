<?php

declare(strict_types=1);

namespace MultiVersion\Cache;

final class ChunkCache {

    private array $cache = [];
    private array $metadata = [];
    private int $defaultTTL;
    private int $maxSize = 5000;
    private int $currentSize = 0;
    private array $statistics = [];

    public function __construct(int $defaultTTL = 3600) {
        $this->defaultTTL = $defaultTTL;
        $this->initializeStatistics();
    }

    private function initializeStatistics(): void {
        $this->statistics = [
            'hits' => 0,
            'misses' => 0,
            'writes' => 0,
            'deletes' => 0,
            'evictions' => 0
        ];
    }

    public function cacheChunk(int $chunkX, int $chunkZ, string $data, int $protocol): bool {
        $key = $this->buildChunkKey($chunkX, $chunkZ, $protocol);

        if ($this->currentSize >= $this->maxSize) {
            $this->evictLRU();
        }

        $isNew = !isset($this->cache[$key]);

        $this->cache[$key] = $data;
        $this->metadata[$key] = [
            'x' => $chunkX,
            'z' => $chunkZ,
            'protocol' => $protocol,
            'created' => microtime(true),
            'last_access' => microtime(true),
            'ttl' => $this->defaultTTL,
            'expires' => microtime(true) + $this->defaultTTL,
            'hits' => 0,
            'size' => strlen($data)
        ];

        if ($isNew) {
            $this->currentSize++;
        }

        $this->statistics['writes']++;
        return true;
    }

    public function getCachedChunk(int $chunkX, int $chunkZ, int $protocol): ?string {
        $key = $this->buildChunkKey($chunkX, $chunkZ, $protocol);

        if (!isset($this->cache[$key])) {
            $this->statistics['misses']++;
            return null;
        }

        $metadata = $this->metadata[$key];

        if ($this->isExpired($metadata)) {
            $this->delete($key);
            $this->statistics['misses']++;
            return null;
        }

        $metadata['hits']++;
        $metadata['last_access'] = microtime(true);
        $this->metadata[$key] = $metadata;

        $this->statistics['hits']++;
        return $this->cache[$key];
    }

    public function hasChunk(int $chunkX, int $chunkZ, int $protocol): bool {
        $key = $this->buildChunkKey($chunkX, $chunkZ, $protocol);

        if (!isset($this->cache[$key])) {
            return false;
        }

        $metadata = $this->metadata[$key];
        return !$this->isExpired($metadata);
    }

    public function deleteChunk(int $chunkX, int $chunkZ, int $protocol): bool {
        $key = $this->buildChunkKey($chunkX, $chunkZ, $protocol);
        return $this->delete($key);
    }

    private function delete(string $key): bool {
        if (!isset($this->cache[$key])) {
            return false;
        }

        unset($this->cache[$key]);
        unset($this->metadata[$key]);
        $this->currentSize--;

        $this->statistics['deletes']++;
        return true;
    }

    public function clear(): void {
        $this->cache = [];
        $this->metadata = [];
        $this->currentSize = 0;
    }

    public function clearProtocol(int $protocol): int {
        $removed = 0;

        foreach ($this->metadata as $key => $meta) {
            if ($meta['protocol'] === $protocol) {
                if ($this->delete($key)) {
                    $removed++;
                }
            }
        }

        return $removed;
    }

    public function clearRegion(int $minX, int $minZ, int $maxX, int $maxZ, int $protocol): int {
        $removed = 0;

        foreach ($this->metadata as $key => $meta) {
            if ($meta['protocol'] !== $protocol) {
                continue;
            }

            $x = $meta['x'];
            $z = $meta['z'];

            if ($x >= $minX && $x <= $maxX && $z >= $minZ && $z <= $maxZ) {
                if ($this->delete($key)) {
                    $removed++;
                }
            }
        }

        return $removed;
    }

    private function buildChunkKey(int $chunkX, int $chunkZ, int $protocol): string {
        return "chunk:{$protocol}:{$chunkX}:{$chunkZ}";
    }

    private function isExpired(array $metadata): bool {
        return microtime(true) > $metadata['expires'];
    }

    private function evictLRU(): void {
        if (empty($this->metadata)) {
            return;
        }

        $lruKey = null;
        $lruScore = PHP_FLOAT_MAX;

        foreach ($this->metadata as $key => $meta) {
            $score = $this->calculateEvictionScore($meta);

            if ($score < $lruScore) {
                $lruScore = $score;
                $lruKey = $key;
            }
        }

        if ($lruKey !== null) {
            $this->delete($lruKey);
            $this->statistics['evictions']++;
        }
    }

    private function calculateEvictionScore(array $metadata): float {
        $age = microtime(true) - $metadata['created'];
        $timeSinceAccess = microtime(true) - $metadata['last_access'];
        $hits = $metadata['hits'];

        $score = $timeSinceAccess * 0.6 + $age * 0.2 - ($hits * 15);

        return $score;
    }

    public function cleanup(): int {
        $removed = 0;
        $now = microtime(true);

        foreach ($this->metadata as $key => $meta) {
            if ($now > $meta['expires']) {
                $this->delete($key);
                $removed++;
            }
        }

        return $removed;
    }

    public function optimize(): void {
        $this->cleanup();

        if ($this->currentSize > ($this->maxSize * 0.8)) {
            $toRemove = (int)($this->maxSize * 0.2);

            for ($i = 0; $i < $toRemove; $i++) {
                $this->evictLRU();
            }
        }
    }

    public function getStatistics(): array {
        $totalRequests = $this->statistics['hits'] + $this->statistics['misses'];
        $hitRate = $totalRequests > 0
            ? round(($this->statistics['hits'] / $totalRequests) * 100, 2)
            : 0;

        return [
            'size' => $this->currentSize,
            'max_size' => $this->maxSize,
            'usage_percent' => round(($this->currentSize / $this->maxSize) * 100, 2),
            'hits' => $this->statistics['hits'],
            'misses' => $this->statistics['misses'],
            'writes' => $this->statistics['writes'],
            'deletes' => $this->statistics['deletes'],
            'evictions' => $this->statistics['evictions'],
            'hit_rate' => $hitRate
        ];
    }

    public function resetStatistics(): void {
        $this->initializeStatistics();
    }

    public function setMaxSize(int $maxSize): void {
        $this->maxSize = max(100, $maxSize);
    }

    public function getMaxSize(): int {
        return $this->maxSize;
    }

    public function setDefaultTTL(int $ttl): void {
        $this->defaultTTL = max(60, $ttl);
    }

    public function getDefaultTTL(): int {
        return $this->defaultTTL;
    }

    public function getSize(): int {
        return $this->currentSize;
    }

    public function getMemoryUsage(): int {
        $total = 0;

        foreach ($this->metadata as $meta) {
            $total += $meta['size'] ?? 0;
        }

        return $total;
    }

    public function getCachedChunks(int $protocol): array {
        $chunks = [];

        foreach ($this->metadata as $key => $meta) {
            if ($meta['protocol'] === $protocol) {
                $chunks[] = [
                    'x' => $meta['x'],
                    'z' => $meta['z'],
                    'hits' => $meta['hits'],
                    'age' => microtime(true) - $meta['created']
                ];
            }
        }

        return $chunks;
    }

    public function getChunksInRadius(int $centerX, int $centerZ, int $radius, int $protocol): array {
        $chunks = [];

        foreach ($this->metadata as $key => $meta) {
            if ($meta['protocol'] !== $protocol) {
                continue;
            }

            $x = $meta['x'];
            $z = $meta['z'];

            $distance = sqrt(pow($x - $centerX, 2) + pow($z - $centerZ, 2));

            if ($distance <= $radius) {
                $chunks[] = [
                    'x' => $x,
                    'z' => $z,
                    'distance' => $distance,
                    'hits' => $meta['hits']
                ];
            }
        }

        usort($chunks, fn($a, $b) => $a['distance'] <=> $b['distance']);

        return $chunks;
    }

    public function preloadChunks(array $chunkCoords, int $protocol): array {
        $loaded = [];

        foreach ($chunkCoords as $coord) {
            $x = $coord['x'];
            $z = $coord['z'];

            if ($this->hasChunk($x, $z, $protocol)) {
                $loaded[] = ['x' => $x, 'z' => $z, 'cached' => true];
            } else {
                $loaded[] = ['x' => $x, 'z' => $z, 'cached' => false];
            }
        }

        return $loaded;
    }

    public function getMetadata(int $chunkX, int $chunkZ, int $protocol): ?array {
        $key = $this->buildChunkKey($chunkX, $chunkZ, $protocol);
        return $this->metadata[$key] ?? null;
    }

    public function touch(int $chunkX, int $chunkZ, int $protocol): bool {
        $key = $this->buildChunkKey($chunkX, $chunkZ, $protocol);

        if (!isset($this->metadata[$key])) {
            return false;
        }

        $this->metadata[$key]['last_access'] = microtime(true);
        return true;
    }

    public function extend(int $chunkX, int $chunkZ, int $protocol, int $additionalTTL): bool {
        $key = $this->buildChunkKey($chunkX, $chunkZ, $protocol);

        if (!isset($this->metadata[$key])) {
            return false;
        }

        $this->metadata[$key]['expires'] += $additionalTTL;
        $this->metadata[$key]['ttl'] += $additionalTTL;
        return true;
    }

    public function getMostAccessed(int $limit = 10): array {
        $chunks = [];

        foreach ($this->metadata as $key => $meta) {
            $chunks[] = [
                'x' => $meta['x'],
                'z' => $meta['z'],
                'protocol' => $meta['protocol'],
                'hits' => $meta['hits'],
                'age' => microtime(true) - $meta['created']
            ];
        }

        usort($chunks, fn($a, $b) => $b['hits'] <=> $a['hits']);

        return array_slice($chunks, 0, $limit);
    }

    public function getOldest(int $limit = 10): array {
        $chunks = [];

        foreach ($this->metadata as $key => $meta) {
            $age = microtime(true) - $meta['created'];

            $chunks[] = [
                'x' => $meta['x'],
                'z' => $meta['z'],
                'protocol' => $meta['protocol'],
                'age' => $age,
                'hits' => $meta['hits']
            ];
        }

        usort($chunks, fn($a, $b) => $b['age'] <=> $a['age']);

        return array_slice($chunks, 0, $limit);
    }
}