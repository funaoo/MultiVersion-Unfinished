<?php

declare(strict_types=1);

namespace MultiVersion\Cache;

final class PacketCache {

    private array $cache = [];
    private array $metadata = [];
    private int $defaultTTL;
    private int $maxSize = 10000;
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

    public function get(string $key): mixed {
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

    public function set(string $key, mixed $value, ?int $ttl = null): bool {
        $ttl = $ttl ?? $this->defaultTTL;

        if ($this->currentSize >= $this->maxSize) {
            $this->evictLRU();
        }

        $isNew = !isset($this->cache[$key]);

        $this->cache[$key] = $value;
        $this->metadata[$key] = [
            'created' => microtime(true),
            'last_access' => microtime(true),
            'ttl' => $ttl,
            'expires' => microtime(true) + $ttl,
            'hits' => 0,
            'size' => $this->calculateSize($value)
        ];

        if ($isNew) {
            $this->currentSize++;
        }

        $this->statistics['writes']++;
        return true;
    }

    public function has(string $key): bool {
        if (!isset($this->cache[$key])) {
            return false;
        }

        $metadata = $this->metadata[$key];
        return !$this->isExpired($metadata);
    }

    public function delete(string $key): bool {
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

    public function cachePacket(string $key, object $packet, int $protocol): bool {
        $fullKey = $this->buildPacketKey($key, $protocol);
        return $this->set($fullKey, clone $packet);
    }

    public function getCachedPacket(string $key, int $protocol): ?object {
        $fullKey = $this->buildPacketKey($key, $protocol);
        $packet = $this->get($fullKey);

        if ($packet === null) {
            return null;
        }

        return clone $packet;
    }

    private function buildPacketKey(string $key, int $protocol): string {
        return "packet:{$protocol}:{$key}";
    }

    private function isExpired(array $metadata): bool {
        return microtime(true) > $metadata['expires'];
    }

    private function evictLRU(): void {
        if (empty($this->metadata)) {
            return;
        }

        $lruKey = null;
        $lruTime = PHP_FLOAT_MAX;

        foreach ($this->metadata as $key => $meta) {
            $score = $this->calculateEvictionScore($meta);

            if ($score < $lruTime) {
                $lruTime = $score;
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

        $score = $timeSinceAccess * 0.5 + $age * 0.3 - ($hits * 10);

        return $score;
    }

    private function calculateSize(mixed $value): int {
        if (is_object($value)) {
            return strlen(serialize($value));
        }

        if (is_array($value)) {
            return strlen(serialize($value));
        }

        if (is_string($value)) {
            return strlen($value);
        }

        return 8;
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

    public function getKeys(): array {
        return array_keys($this->cache);
    }

    public function getKeysByPattern(string $pattern): array {
        $keys = [];

        foreach ($this->cache as $key => $value) {
            if (fnmatch($pattern, $key)) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    public function deleteByPattern(string $pattern): int {
        $deleted = 0;
        $keys = $this->getKeysByPattern($pattern);

        foreach ($keys as $key) {
            if ($this->delete($key)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    public function getMetadata(string $key): ?array {
        return $this->metadata[$key] ?? null;
    }

    public function touch(string $key): bool {
        if (!isset($this->metadata[$key])) {
            return false;
        }

        $this->metadata[$key]['last_access'] = microtime(true);
        return true;
    }

    public function extend(string $key, int $additionalTTL): bool {
        if (!isset($this->metadata[$key])) {
            return false;
        }

        $this->metadata[$key]['expires'] += $additionalTTL;
        $this->metadata[$key]['ttl'] += $additionalTTL;
        return true;
    }
}