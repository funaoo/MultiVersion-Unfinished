<?php

declare(strict_types=1);

namespace MultiVersion\Translator;

use MultiVersion\MultiVersion;

final class BiomeTranslator {

    private array $mappings = [];
    private array $reverseMappings = [];
    private array $biomeData = [];
    private array $translationCache = [];
    private int $cacheSize = 0;
    private int $maxCacheSize = 2000;

    public function __construct() {
        $this->loadMappings();
        $this->loadBiomeData();
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
        $dataPath = MultiVersion::getInstance()->getDataFolder() . "data/mappings/biomes/";
        $file = $dataPath . "{$from}_to_{$to}.json";

        if (!file_exists($file)) {
            return $this->generateDefaultMapping($from, $to);
        }

        $content = file_get_contents($file);
        return json_decode($content, true) ?? [];
    }

    private function generateDefaultMapping(int $from, int $to): array {
        $mapping = [];

        $commonBiomes = [
            0 => 0,
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
            6 => 6,
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
            25 => 25,
            26 => 26,
            27 => 27,
            28 => 28,
            29 => 29,
            30 => 30,
            31 => 31,
            32 => 32,
            33 => 33,
            34 => 34,
            35 => 35,
            36 => 36,
            37 => 37,
            38 => 38,
            39 => 39,
            40 => 40
        ];

        foreach ($commonBiomes as $fromBiome => $toBiome) {
            $mapping[$fromBiome] = $toBiome;
        }

        return $mapping;
    }

    private function loadBiomeData(): void {
        $protocols = [621, 594, 527];

        foreach ($protocols as $protocol) {
            $this->biomeData[$protocol] = $this->loadBiomeDataFile($protocol);
        }
    }

    private function loadBiomeDataFile(int $protocol): array {
        $dataPath = MultiVersion::getInstance()->getDataFolder() . "data/resources/";
        $file = $dataPath . "biomes_{$protocol}.json";

        if (!file_exists($file)) {
            return $this->generateDefaultBiomeData();
        }

        $content = file_get_contents($file);
        return json_decode($content, true) ?? [];
    }

    private function generateDefaultBiomeData(): array {
        $biomes = [];

        $defaultBiomes = [
            0 => ['name' => 'ocean', 'temperature' => 0.5, 'rainfall' => 0.5, 'category' => 'ocean'],
            1 => ['name' => 'plains', 'temperature' => 0.8, 'rainfall' => 0.4, 'category' => 'plains'],
            2 => ['name' => 'desert', 'temperature' => 2.0, 'rainfall' => 0.0, 'category' => 'desert'],
            3 => ['name' => 'extreme_hills', 'temperature' => 0.2, 'rainfall' => 0.3, 'category' => 'extreme_hills'],
            4 => ['name' => 'forest', 'temperature' => 0.7, 'rainfall' => 0.8, 'category' => 'forest'],
            5 => ['name' => 'taiga', 'temperature' => 0.25, 'rainfall' => 0.8, 'category' => 'taiga'],
            6 => ['name' => 'swampland', 'temperature' => 0.8, 'rainfall' => 0.9, 'category' => 'swamp'],
            7 => ['name' => 'river', 'temperature' => 0.5, 'rainfall' => 0.5, 'category' => 'river'],
            8 => ['name' => 'hell', 'temperature' => 2.0, 'rainfall' => 0.0, 'category' => 'nether'],
            9 => ['name' => 'the_end', 'temperature' => 0.5, 'rainfall' => 0.5, 'category' => 'the_end'],
            10 => ['name' => 'frozen_ocean', 'temperature' => 0.0, 'rainfall' => 0.5, 'category' => 'ocean'],
            11 => ['name' => 'frozen_river', 'temperature' => 0.0, 'rainfall' => 0.5, 'category' => 'river'],
            12 => ['name' => 'ice_plains', 'temperature' => 0.0, 'rainfall' => 0.5, 'category' => 'icy'],
            13 => ['name' => 'ice_mountains', 'temperature' => 0.0, 'rainfall' => 0.5, 'category' => 'icy'],
            14 => ['name' => 'mushroom_island', 'temperature' => 0.9, 'rainfall' => 1.0, 'category' => 'mushroom'],
            15 => ['name' => 'mushroom_island_shore', 'temperature' => 0.9, 'rainfall' => 1.0, 'category' => 'mushroom'],
            16 => ['name' => 'beach', 'temperature' => 0.8, 'rainfall' => 0.4, 'category' => 'beach'],
            17 => ['name' => 'desert_hills', 'temperature' => 2.0, 'rainfall' => 0.0, 'category' => 'desert'],
            18 => ['name' => 'forest_hills', 'temperature' => 0.7, 'rainfall' => 0.8, 'category' => 'forest'],
            19 => ['name' => 'taiga_hills', 'temperature' => 0.25, 'rainfall' => 0.8, 'category' => 'taiga'],
            20 => ['name' => 'extreme_hills_edge', 'temperature' => 0.2, 'rainfall' => 0.3, 'category' => 'extreme_hills'],
            21 => ['name' => 'jungle', 'temperature' => 0.95, 'rainfall' => 0.9, 'category' => 'jungle'],
            22 => ['name' => 'jungle_hills', 'temperature' => 0.95, 'rainfall' => 0.9, 'category' => 'jungle'],
            23 => ['name' => 'jungle_edge', 'temperature' => 0.95, 'rainfall' => 0.8, 'category' => 'jungle'],
            24 => ['name' => 'deep_ocean', 'temperature' => 0.5, 'rainfall' => 0.5, 'category' => 'ocean'],
            25 => ['name' => 'stone_beach', 'temperature' => 0.2, 'rainfall' => 0.3, 'category' => 'beach'],
            26 => ['name' => 'cold_beach', 'temperature' => 0.05, 'rainfall' => 0.3, 'category' => 'beach'],
            27 => ['name' => 'birch_forest', 'temperature' => 0.6, 'rainfall' => 0.6, 'category' => 'forest'],
            28 => ['name' => 'birch_forest_hills', 'temperature' => 0.6, 'rainfall' => 0.6, 'category' => 'forest'],
            29 => ['name' => 'roofed_forest', 'temperature' => 0.7, 'rainfall' => 0.8, 'category' => 'forest'],
            30 => ['name' => 'cold_taiga', 'temperature' => -0.5, 'rainfall' => 0.4, 'category' => 'taiga'],
            31 => ['name' => 'cold_taiga_hills', 'temperature' => -0.5, 'rainfall' => 0.4, 'category' => 'taiga'],
            32 => ['name' => 'mega_taiga', 'temperature' => 0.3, 'rainfall' => 0.8, 'category' => 'taiga'],
            33 => ['name' => 'mega_taiga_hills', 'temperature' => 0.3, 'rainfall' => 0.8, 'category' => 'taiga'],
            34 => ['name' => 'extreme_hills_plus', 'temperature' => 0.2, 'rainfall' => 0.3, 'category' => 'extreme_hills'],
            35 => ['name' => 'savanna', 'temperature' => 1.2, 'rainfall' => 0.0, 'category' => 'savanna'],
            36 => ['name' => 'savanna_plateau', 'temperature' => 1.0, 'rainfall' => 0.0, 'category' => 'savanna'],
            37 => ['name' => 'mesa', 'temperature' => 2.0, 'rainfall' => 0.0, 'category' => 'mesa'],
            38 => ['name' => 'mesa_plateau_f', 'temperature' => 2.0, 'rainfall' => 0.0, 'category' => 'mesa'],
            39 => ['name' => 'mesa_plateau', 'temperature' => 2.0, 'rainfall' => 0.0, 'category' => 'mesa'],
            40 => ['name' => 'warm_ocean', 'temperature' => 0.5, 'rainfall' => 0.5, 'category' => 'ocean']
        ];

        foreach ($defaultBiomes as $id => $data) {
            $biomes[$id] = $data;
        }

        return $biomes;
    }

    public function translate(int $biomeId, int $fromProtocol, int $toProtocol): int {
        if ($fromProtocol === $toProtocol) {
            return $biomeId;
        }

        $cacheKey = "{$biomeId}_{$fromProtocol}_{$toProtocol}";

        if (isset($this->translationCache[$cacheKey])) {
            return $this->translationCache[$cacheKey];
        }

        $translated = $this->performTranslation($biomeId, $fromProtocol, $toProtocol);

        $this->cacheTranslation($cacheKey, $translated);

        return $translated;
    }

    private function performTranslation(int $biomeId, int $fromProtocol, int $toProtocol): int {
        $mappingKey = "{$fromProtocol}_{$toProtocol}";

        if (isset($this->mappings[$mappingKey][$biomeId])) {
            return $this->mappings[$mappingKey][$biomeId];
        }

        $fallback = $this->findFallbackBiome($biomeId, $fromProtocol, $toProtocol);

        if ($fallback !== null) {
            return $fallback;
        }

        return 0;
    }

    private function findFallbackBiome(int $biomeId, int $fromProtocol, int $toProtocol): ?int {
        $fromData = $this->getBiomeData($biomeId, $fromProtocol);

        if ($fromData === null) {
            return null;
        }

        $biomeName = $fromData['name'] ?? null;

        if ($biomeName === null) {
            return null;
        }

        $toData = $this->biomeData[$toProtocol] ?? [];

        foreach ($toData as $id => $data) {
            if (($data['name'] ?? null) === $biomeName) {
                return $id;
            }
        }

        return $this->getSimilarBiome($fromData, $toProtocol);
    }

    private function getSimilarBiome(array $biomeData, int $protocol): ?int {
        $category = $biomeData['category'] ?? null;
        $temperature = $biomeData['temperature'] ?? 0.5;

        if ($category === null) {
            return null;
        }

        $toData = $this->biomeData[$protocol] ?? [];
        $bestMatch = null;
        $bestScore = PHP_FLOAT_MAX;

        foreach ($toData as $id => $data) {
            if (($data['category'] ?? null) === $category) {
                $tempDiff = abs(($data['temperature'] ?? 0.5) - $temperature);

                if ($tempDiff < $bestScore) {
                    $bestScore = $tempDiff;
                    $bestMatch = $id;
                }
            }
        }

        return $bestMatch;
    }

    private function getBiomeData(int $biomeId, int $protocol): ?array {
        return $this->biomeData[$protocol][$biomeId] ?? null;
    }

    private function cacheTranslation(string $cacheKey, int $translated): void {
        if ($this->cacheSize >= $this->maxCacheSize) {
            $this->cleanCache();
        }

        $this->translationCache[$cacheKey] = $translated;
        $this->cacheSize++;
    }

    private function cleanCache(): void {
        $this->translationCache = array_slice($this->translationCache, -1000, 1000, true);
        $this->cacheSize = count($this->translationCache);
    }

    public function translateBiome(array $biome, int $fromProtocol, int $toProtocol): array {
        $biomeId = $biome['id'] ?? 0;

        $translatedId = $this->translate($biomeId, $fromProtocol, $toProtocol);

        return [
            'id' => $translatedId,
            'name' => $this->getBiomeName($translatedId, $toProtocol),
            'temperature' => $this->getBiomeTemperature($translatedId, $toProtocol),
            'rainfall' => $this->getBiomeRainfall($translatedId, $toProtocol),
            'category' => $this->getBiomeCategory($translatedId, $toProtocol)
        ];
    }

    public function getBiomeName(int $biomeId, int $protocol): string {
        $data = $this->getBiomeData($biomeId, $protocol);
        return $data['name'] ?? 'ocean';
    }

    public function getBiomeTemperature(int $biomeId, int $protocol): float {
        $data = $this->getBiomeData($biomeId, $protocol);
        return $data['temperature'] ?? 0.5;
    }

    public function getBiomeRainfall(int $biomeId, int $protocol): float {
        $data = $this->getBiomeData($biomeId, $protocol);
        return $data['rainfall'] ?? 0.5;
    }

    public function getBiomeCategory(int $biomeId, int $protocol): string {
        $data = $this->getBiomeData($biomeId, $protocol);
        return $data['category'] ?? 'ocean';
    }

    public function getBiomeId(string $biomeName, int $protocol): ?int {
        $biomes = $this->biomeData[$protocol] ?? [];

        foreach ($biomes as $id => $data) {
            if (($data['name'] ?? '') === $biomeName) {
                return $id;
            }
        }

        return null;
    }

    public function translatePacket(object $packet, int $fromProtocol, int $toProtocol): object {
        $packetClass = get_class($packet);

        if (str_contains($packetClass, 'BiomeDefinitionList')) {
            return $this->translateBiomeDefinitionListPacket($packet, $fromProtocol, $toProtocol);
        }

        if (str_contains($packetClass, 'LevelChunk')) {
            return $this->translateLevelChunkBiomes($packet, $fromProtocol, $toProtocol);
        }

        return $packet;
    }

    private function translateBiomeDefinitionListPacket(object $packet, int $fromProtocol, int $toProtocol): object {
        return $packet;
    }

    private function translateLevelChunkBiomes(object $packet, int $fromProtocol, int $toProtocol): object {
        return $packet;
    }

    public function translateBiomeArray(array $biomes, int $fromProtocol, int $toProtocol): array {
        $translated = [];

        foreach ($biomes as $biomeId) {
            $translated[] = $this->translate($biomeId, $fromProtocol, $toProtocol);
        }

        return $translated;
    }

    public function addMapping(int $fromBiome, int $toBiome, int $fromProtocol, int $toProtocol): void {
        $mappingKey = "{$fromProtocol}_{$toProtocol}";

        if (!isset($this->mappings[$mappingKey])) {
            $this->mappings[$mappingKey] = [];
        }

        $this->mappings[$mappingKey][$fromBiome] = $toBiome;

        $reverseMappingKey = "{$toProtocol}_{$fromProtocol}";
        if (!isset($this->reverseMappings[$reverseMappingKey])) {
            $this->reverseMappings[$reverseMappingKey] = [];
        }
        $this->reverseMappings[$reverseMappingKey][$toBiome] = $fromBiome;
    }

    public function hasMapping(int $biomeId, int $fromProtocol, int $toProtocol): bool {
        $mappingKey = "{$fromProtocol}_{$toProtocol}";
        return isset($this->mappings[$mappingKey][$biomeId]);
    }

    public function clearCache(): void {
        $this->translationCache = [];
        $this->cacheSize = 0;
    }

    public function getStatistics(): array {
        return [
            'cache_size' => $this->cacheSize,
            'mappings_loaded' => count($this->mappings),
            'biomes_loaded' => array_sum(array_map('count', $this->biomeData))
        ];
    }

    public function saveMappings(): void {
        $dataPath = MultiVersion::getInstance()->getDataFolder() . "data/mappings/biomes/";

        if (!is_dir($dataPath)) {
            mkdir($dataPath, 0777, true);
        }

        foreach ($this->mappings as $key => $mapping) {
            $file = $dataPath . "{$key}.json";
            file_put_contents($file, json_encode($mapping, JSON_PRETTY_PRINT));
        }
    }
}