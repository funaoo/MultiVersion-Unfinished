<?php

declare(strict_types=1);

namespace MultiVersion\Translator;

use MultiVersion\MultiVersion;

final class BlockTranslator {

    private array $mappings = [];
    private array $reverseMappings = [];
    private array $blockStates = [];
    private array $translationCache = [];
    private int $cacheSize = 0;
    private int $maxCacheSize = 10000;

    public function __construct() {
        $this->loadMappings();
        $this->loadBlockStates();
    }

    private function loadMappings(): void {
        $protocols = [621, 594, 527];

        foreach ($protocols as $from) {
            foreach ($protocols as $to) {
                if ($from === $to) continue;

                $this->mappings["{$from}_{$to}"] = $this->loadMappingFile($from, $to);
                $this->reverseMappings["{$to}_{$from}"] = array_flip($this->mappings["{$from}_{$to}"]);
            }
        }
    }

    private function loadMappingFile(int $from, int $to): array {
        $dataPath = MultiVersion::getInstance()->getDataFolder() . "data/mappings/blocks/";
        $file = $dataPath . "{$from}_to_{$to}.json";

        if (!file_exists($file)) {
            return $this->generateDefaultMapping($from, $to);
        }

        $content = file_get_contents($file);
        return json_decode($content, true) ?? [];
    }

    private function generateDefaultMapping(int $from, int $to): array {
        $mapping = [];

        $commonBlocks = [
            0 => 0,
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
            7 => 7,
            8 => 8,
            9 => 9,
            10 => 10,
            11 => 11,
            12 => 12,
            13 => 13,
            14 => 14,
            15 => 15,
            16 => 16,
            17 => 17,
            18 => 18,
            19 => 19,
            20 => 20,
            21 => 21,
            22 => 22,
            23 => 23,
            24 => 24,
            25 => 25
        ];

        foreach ($commonBlocks as $fromBlock => $toBlock) {
            $mapping[$fromBlock] = $toBlock;
        }

        return $mapping;
    }

    private function loadBlockStates(): void {
        $protocols = [621, 594, 527];

        foreach ($protocols as $protocol) {
            $this->blockStates[$protocol] = $this->loadBlockStatesFile($protocol);
        }
    }

    private function loadBlockStatesFile(int $protocol): array {
        $dataPath = MultiVersion::getInstance()->getDataFolder() . "data/protocol/{$protocol}/";
        $file = $dataPath . "block_states.json";

        if (!file_exists($file)) {
            return $this->generateDefaultBlockStates();
        }

        $content = file_get_contents($file);
        return json_decode($content, true) ?? [];
    }

    private function generateDefaultBlockStates(): array {
        $states = [];

        for ($i = 0; $i < 1000; $i++) {
            $states[$i] = [
                'name' => 'minecraft:unknown',
                'properties' => [],
                'runtime_id' => $i
            ];
        }

        return $states;
    }

    public function translate(int $blockId, int $fromProtocol, int $toProtocol): int {
        if ($fromProtocol === $toProtocol) {
            return $blockId;
        }

        $cacheKey = "{$blockId}_{$fromProtocol}_{$toProtocol}";

        if (isset($this->translationCache[$cacheKey])) {
            return $this->translationCache[$cacheKey];
        }

        $translated = $this->performTranslation($blockId, $fromProtocol, $toProtocol);

        $this->cacheTranslation($cacheKey, $translated);

        return $translated;
    }

    private function performTranslation(int $blockId, int $fromProtocol, int $toProtocol): int {
        $mappingKey = "{$fromProtocol}_{$toProtocol}";

        if (isset($this->mappings[$mappingKey][$blockId])) {
            return $this->mappings[$mappingKey][$blockId];
        }

        $fallback = $this->findFallbackBlock($blockId, $fromProtocol, $toProtocol);

        if ($fallback !== null) {
            return $fallback;
        }

        return 0;
    }

    private function findFallbackBlock(int $blockId, int $fromProtocol, int $toProtocol): ?int {
        $fromState = $this->getBlockState($blockId, $fromProtocol);

        if ($fromState === null) {
            return null;
        }

        $blockName = $fromState['name'] ?? null;

        if ($blockName === null) {
            return null;
        }

        $toStates = $this->blockStates[$toProtocol] ?? [];

        foreach ($toStates as $runtimeId => $state) {
            if (($state['name'] ?? null) === $blockName) {
                return $runtimeId;
            }
        }

        return $this->getSimilarBlock($blockName, $toProtocol);
    }

    private function getSimilarBlock(string $blockName, int $protocol): ?int {
        $baseName = $this->extractBaseName($blockName);
        $toStates = $this->blockStates[$protocol] ?? [];

        foreach ($toStates as $runtimeId => $state) {
            $stateName = $state['name'] ?? '';

            if ($this->extractBaseName($stateName) === $baseName) {
                return $runtimeId;
            }
        }

        return null;
    }

    private function extractBaseName(string $blockName): string {
        $parts = explode(':', $blockName);
        $name = $parts[1] ?? $blockName;

        $name = preg_replace('/_\d+$/', '', $name);

        return $name;
    }

    private function getBlockState(int $blockId, int $protocol): ?array {
        return $this->blockStates[$protocol][$blockId] ?? null;
    }

    private function cacheTranslation(string $cacheKey, int $translated): void {
        if ($this->cacheSize >= $this->maxCacheSize) {
            $this->cleanCache();
        }

        $this->translationCache[$cacheKey] = $translated;
        $this->cacheSize++;
    }

    private function cleanCache(): void {
        $this->translationCache = array_slice($this->translationCache, -5000, 5000, true);
        $this->cacheSize = count($this->translationCache);
    }

    public function translateBlock(array $block, int $fromProtocol, int $toProtocol): array {
        $blockId = $block['id'] ?? 0;
        $blockData = $block['data'] ?? 0;

        $translatedId = $this->translate($blockId, $fromProtocol, $toProtocol);
        $translatedData = $this->translateBlockData($blockId, $blockData, $fromProtocol, $toProtocol);

        return [
            'id' => $translatedId,
            'data' => $translatedData,
            'name' => $this->getBlockName($translatedId, $toProtocol)
        ];
    }

    private function translateBlockData(int $blockId, int $blockData, int $fromProtocol, int $toProtocol): int {
        $fromState = $this->getBlockState($blockId, $fromProtocol);

        if ($fromState === null) {
            return $blockData;
        }

        $properties = $fromState['properties'] ?? [];

        if (empty($properties)) {
            return $blockData;
        }

        return $this->convertBlockData($properties, $blockData, $toProtocol);
    }

    private function convertBlockData(array $properties, int $blockData, int $toProtocol): int {
        return $blockData;
    }

    public function getBlockName(int $blockId, int $protocol): string {
        $state = $this->getBlockState($blockId, $protocol);
        return $state['name'] ?? 'minecraft:air';
    }

    public function getRuntimeId(string $blockName, int $protocol, array $properties = []): ?int {
        $states = $this->blockStates[$protocol] ?? [];

        foreach ($states as $runtimeId => $state) {
            if (($state['name'] ?? '') === $blockName) {
                if ($this->propertiesMatch($state['properties'] ?? [], $properties)) {
                    return $runtimeId;
                }
            }
        }

        return null;
    }

    private function propertiesMatch(array $stateProperties, array $requestedProperties): bool {
        if (empty($requestedProperties)) {
            return true;
        }

        foreach ($requestedProperties as $key => $value) {
            if (!isset($stateProperties[$key]) || $stateProperties[$key] !== $value) {
                return false;
            }
        }

        return true;
    }

    public function translatePacket(object $packet, int $fromProtocol, int $toProtocol): object {
        $packetClass = get_class($packet);

        if (str_contains($packetClass, 'UpdateBlock')) {
            return $this->translateUpdateBlockPacket($packet, $fromProtocol, $toProtocol);
        }

        if (str_contains($packetClass, 'LevelChunk')) {
            return $this->translateLevelChunkPacket($packet, $fromProtocol, $toProtocol);
        }

        return $packet;
    }

    private function translateUpdateBlockPacket(object $packet, int $fromProtocol, int $toProtocol): object {
        if (isset($packet->blockRuntimeId)) {
            $packet->blockRuntimeId = $this->translate($packet->blockRuntimeId, $fromProtocol, $toProtocol);
        }

        return $packet;
    }

    private function translateLevelChunkPacket(object $packet, int $fromProtocol, int $toProtocol): object {
        return $packet;
    }

    public function addMapping(int $fromBlock, int $toBlock, int $fromProtocol, int $toProtocol): void {
        $mappingKey = "{$fromProtocol}_{$toProtocol}";

        if (!isset($this->mappings[$mappingKey])) {
            $this->mappings[$mappingKey] = [];
        }

        $this->mappings[$mappingKey][$fromBlock] = $toBlock;

        $reverseMappingKey = "{$toProtocol}_{$fromProtocol}";
        if (!isset($this->reverseMappings[$reverseMappingKey])) {
            $this->reverseMappings[$reverseMappingKey] = [];
        }
        $this->reverseMappings[$reverseMappingKey][$toBlock] = $fromBlock;
    }

    public function hasMapping(int $blockId, int $fromProtocol, int $toProtocol): bool {
        $mappingKey = "{$fromProtocol}_{$toProtocol}";
        return isset($this->mappings[$mappingKey][$blockId]);
    }

    public function clearCache(): void {
        $this->translationCache = [];
        $this->cacheSize = 0;
    }

    public function getStatistics(): array {
        return [
            'cache_size' => $this->cacheSize,
            'mappings_loaded' => count($this->mappings),
            'block_states_loaded' => array_sum(array_map('count', $this->blockStates))
        ];
    }

    public function saveMappings(): void {
        $dataPath = MultiVersion::getInstance()->getDataFolder() . "data/mappings/blocks/";

        if (!is_dir($dataPath)) {
            mkdir($dataPath, 0777, true);
        }

        foreach ($this->mappings as $key => $mapping) {
            $file = $dataPath . "{$key}.json";
            file_put_contents($file, json_encode($mapping, JSON_PRETTY_PRINT));
        }
    }
}