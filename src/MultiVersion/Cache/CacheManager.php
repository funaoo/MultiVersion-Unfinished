<?php

declare(strict_types=1);

namespace MultiVersion\Cache;

use MultiVersion\Translator\BiomeTranslator;
use MultiVersion\Translator\BlockTranslator;
use MultiVersion\Translator\EntityTranslator;
use MultiVersion\Translator\ItemTranslator;
use MultiVersion\Utils\Config;

final class CacheManager {

    private Config $config;
    private PacketCache $packetCache;
    private ChunkCache $chunkCache;
    private array $translators = [];
    private bool $enabled;
    private int $ttl;
    private array $statistics = [];

    public function __construct(Config $config) {
        $this->config = $config;
        $this->enabled = $config->isCachingEnabled();
        $this->ttl = $config->getCacheTTL();

        $this->initializeCaches();
        $this->initializeTranslators();
        $this->initializeStatistics();
    }

    private function initializeCaches(): void {
        $this->packetCache = new PacketCache($this->ttl);
        $this->chunkCache = new ChunkCache($this->ttl);
    }

    private function initializeTranslators(): void {
        $this->translators = [
            'block' => new BlockTranslator(),
            'item' => new ItemTranslator(),
            'entity' => new EntityTranslator(),
            'biome' => new BiomeTranslator()
        ];
    }

    private function initializeStatistics(): void {
        $this->statistics = [
            'total_hits' => 0,
            'total_misses' => 0,
            'total_writes' => 0,
            'total_evictions' => 0,
            'start_time' => microtime(true)
        ];
    }

    public function get(string $key): mixed {
        if (!$this->enabled) {
            $this->statistics['total_misses']++;
            return null;
        }

        $value = $this->packetCache->get($key);

        if ($value !== null) {
            $this->statistics['total_hits']++;
            return $value;
        }

        $this->statistics['total_misses']++;
        return null;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool {
        if (!$this->enabled) {
            return false;
        }

        $result = $this->packetCache->set($key, $value, $ttl ?? $this->ttl);

        if ($result) {
            $this->statistics['total_writes']++;
        }

        return $result;
    }

    public function has(string $key): bool {
        if (!$this->enabled) {
            return false;
        }

        return $this->packetCache->has($key);
    }

    public function delete(string $key): bool {
        if (!$this->enabled) {
            return false;
        }

        return $this->packetCache->delete($key);
    }

    public function clear(): void {
        $this->packetCache->clear();
        $this->chunkCache->clear();

        foreach ($this->translators as $translator) {
            if (method_exists($translator, 'clearCache')) {
                $translator->clearCache();
            }
        }
    }

    public function getPacketCache(): PacketCache {
        return $this->packetCache;
    }

    public function getChunkCache(): ChunkCache {
        return $this->chunkCache;
    }

    public function getTranslator(string $type): ?object {
        return $this->translators[$type] ?? null;
    }

    public function cachePacket(string $key, object $packet, int $protocol): bool {
        if (!$this->enabled) {
            return false;
        }

        return $this->packetCache->cachePacket($key, $packet, $protocol);
    }

    public function getCachedPacket(string $key, int $protocol): ?object {
        if (!$this->enabled) {
            return null;
        }

        return $this->packetCache->getCachedPacket($key, $protocol);
    }

    public function cacheChunk(int $chunkX, int $chunkZ, string $data, int $protocol): bool {
        if (!$this->enabled) {
            return false;
        }

        return $this->chunkCache->cacheChunk($chunkX, $chunkZ, $data, $protocol);
    }

    public function getCachedChunk(int $chunkX, int $chunkZ, int $protocol): ?string {
        if (!$this->enabled) {
            return null;
        }

        return $this->chunkCache->getCachedChunk($chunkX, $chunkZ, $protocol);
    }

    public function cleanup(): void {
        $evictions = 0;

        $evictions += $this->packetCache->cleanup();
        $evictions += $this->chunkCache->cleanup();

        $this->statistics['total_evictions'] += $evictions;
    }

    public function getStatistics(): array {
        $totalRequests = $this->statistics['total_hits'] + $this->statistics['total_misses'];
        $hitRate = $totalRequests > 0
            ? round(($this->statistics['total_hits'] / $totalRequests) * 100, 2)
            : 0;

        $uptime = microtime(true) - $this->statistics['start_time'];

        return [
            'enabled' => $this->enabled,
            'ttl' => $this->ttl,
            'total_hits' => $this->statistics['total_hits'],
            'total_misses' => $this->statistics['total_misses'],
            'total_writes' => $this->statistics['total_writes'],
            'total_evictions' => $this->statistics['total_evictions'],
            'hit_rate' => $hitRate,
            'uptime' => round($uptime, 2),
            'packet_cache' => $this->packetCache->getStatistics(),
            'chunk_cache' => $this->chunkCache->getStatistics(),
            'translators' => $this->getTranslatorStatistics()
        ];
    }

    private function getTranslatorStatistics(): array {
        $stats = [];

        foreach ($this->translators as $name => $translator) {
            if (method_exists($translator, 'getStatistics')) {
                $stats[$name] = $translator->getStatistics();
            }
        }

        return $stats;
    }

    public function resetStatistics(): void {
        $this->initializeStatistics();
        $this->packetCache->resetStatistics();
        $this->chunkCache->resetStatistics();
    }

    public function setEnabled(bool $enabled): void {
        $this->enabled = $enabled;
    }

    public function isEnabled(): bool {
        return $this->enabled;
    }

    public function setTTL(int $ttl): void {
        $this->ttl = max(60, $ttl);
        $this->packetCache->setDefaultTTL($ttl);
        $this->chunkCache->setDefaultTTL($ttl);
    }

    public function getTTL(): int {
        return $this->ttl;
    }

    public function getMemoryUsage(): array {
        return [
            'packet_cache' => $this->packetCache->getMemoryUsage(),
            'chunk_cache' => $this->chunkCache->getMemoryUsage(),
            'total' => $this->packetCache->getMemoryUsage() + $this->chunkCache->getMemoryUsage()
        ];
    }

    public function optimize(): void {
        $this->packetCache->optimize();
        $this->chunkCache->optimize();
        $this->cleanup();
    }

    public function shutdown(): void {
        $this->clear();
        $this->resetStatistics();
    }
}