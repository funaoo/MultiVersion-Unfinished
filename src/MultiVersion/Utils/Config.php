<?php

declare(strict_types=1);

namespace MultiVersion\Utils;

use pocketmine\utils\Config as PMConfig;

final class Config {

    private string $dataFolder;
    private PMConfig $config;
    private array $cache = [];

    public function __construct(string $dataFolder) {
        $this->dataFolder = $dataFolder;
        $this->loadConfig();
    }

    private function loadConfig(): void {
        $configFile = $this->dataFolder . "multiversion.yml";

        if (!file_exists($configFile)) {
            $this->createDefaultConfig();
        }

        $this->config = new PMConfig($configFile, PMConfig::YAML);
        $this->cache = [];
    }

    private function createDefaultConfig(): void {
        if (!is_dir($this->dataFolder)) {
            mkdir($this->dataFolder, 0777, true);
        }

        $defaultConfig = [
            'enabled' => true,
            'default-protocol' => 621,
            'supported-versions' => [621, 594, 527],
            'translation' => [
                'enable-caching' => true,
                'cache-ttl' => 3600
            ],
            'logging' => [
                'log-packets' => false,
                'log-translations' => true,
                'log-level' => 'INFO'
            ],
            'performance' => [
                'max-cache-size' => 10000,
                'chunk-cache-size' => 5000,
                'cleanup-interval' => 300
            ],
            'features' => [
                'auto-protocol-detection' => true,
                'fallback-translation' => true,
                'strict-validation' => false
            ]
        ];

        $configFile = $this->dataFolder . "multiversion.yml";
        file_put_contents($configFile, yaml_emit($defaultConfig));
    }

    public function isEnabled(): bool {
        return $this->get('enabled', true);
    }

    public function getDefaultProtocol(): int {
        return $this->get('default-protocol', 621);
    }

    public function getSupportedVersions(): array {
        return $this->get('supported-versions', [621, 594, 527]);
    }

    public function isCachingEnabled(): bool {
        return $this->get('translation.enable-caching', true);
    }

    public function getCacheTTL(): int {
        return $this->get('translation.cache-ttl', 3600);
    }

    public function shouldLogPackets(): bool {
        return $this->get('logging.log-packets', false);
    }

    public function shouldLogTranslations(): bool {
        return $this->get('logging.log-translations', true);
    }

    public function getLogLevel(): string {
        return strtoupper($this->get('logging.log-level', 'INFO'));
    }

    public function getMaxCacheSize(): int {
        return $this->get('performance.max-cache-size', 10000);
    }

    public function getChunkCacheSize(): int {
        return $this->get('performance.chunk-cache-size', 5000);
    }

    public function getCleanupInterval(): int {
        return $this->get('performance.cleanup-interval', 300);
    }

    public function isAutoProtocolDetectionEnabled(): bool {
        return $this->get('features.auto-protocol-detection', true);
    }

    public function isFallbackTranslationEnabled(): bool {
        return $this->get('features.fallback-translation', true);
    }

    public function isStrictValidationEnabled(): bool {
        return $this->get('features.strict-validation', false);
    }

    public function get(string $key, mixed $default = null): mixed {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $keys = explode('.', $key);
        $value = $this->config->getAll();

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        $this->cache[$key] = $value;
        return $value;
    }

    public function set(string $key, mixed $value): void {
        $keys = explode('.', $key);
        $config = $this->config->getAll();
        $current = &$config;

        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                $current[$k] = $value;
            } else {
                if (!isset($current[$k]) || !is_array($current[$k])) {
                    $current[$k] = [];
                }
                $current = &$current[$k];
            }
        }

        $this->config->setAll($config);
        $this->cache[$key] = $value;
    }

    public function save(): void {
        $this->config->save();
    }

    public function reload(): void {
        $this->config->reload();
        $this->cache = [];
    }

    public function getAll(): array {
        return $this->config->getAll();
    }

    public function exists(string $key): bool {
        return $this->get($key) !== null;
    }
}