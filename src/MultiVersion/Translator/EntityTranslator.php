<?php

declare(strict_types=1);

namespace MultiVersion\Translator;

use MultiVersion\MultiVersion;

final class EntityTranslator {

    private array $mappings = [];
    private array $reverseMappings = [];
    private array $entityData = [];
    private array $translationCache = [];
    private array $metadataTranslations = [];
    private int $cacheSize = 0;
    private int $maxCacheSize = 5000;

    public function __construct() {
        $this->loadMappings();
        $this->loadEntityData();
        $this->initializeMetadataTranslations();
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
        $dataPath = MultiVersion::getInstance()->getDataFolder() . "data/mappings/entities/";
        $file = $dataPath . "{$from}_to_{$to}.json";

        if (!file_exists($file)) {
            return $this->generateDefaultMapping($from, $to);
        }

        $content = file_get_contents($file);
        return json_decode($content, true) ?? [];
    }

    private function generateDefaultMapping(int $from, int $to): array {
        $mapping = [];

        $commonEntities = [
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
            32 => 32,
            33 => 33,
            34 => 34,
            35 => 35,
            36 => 36,
            37 => 37,
            38 => 38,
            39 => 39,
            40 => 40,
            41 => 41,
            42 => 42,
            43 => 43,
            44 => 44,
            45 => 45,
            46 => 46,
            47 => 47,
            48 => 48,
            49 => 49,
            50 => 50
        ];

        foreach ($commonEntities as $fromEntity => $toEntity) {
            $mapping[$fromEntity] = $toEntity;
        }

        return $mapping;
    }

    private function loadEntityData(): void {
        $protocols = [621, 594, 527];

        foreach ($protocols as $protocol) {
            $this->entityData[$protocol] = $this->loadEntityDataFile($protocol);
        }
    }

    private function loadEntityDataFile(int $protocol): array {
        $dataPath = MultiVersion::getInstance()->getDataFolder() . "data/protocol/{$protocol}/";
        $file = $dataPath . "entities.json";

        if (!file_exists($file)) {
            return $this->generateDefaultEntityData();
        }

        $content = file_get_contents($file);
        return json_decode($content, true) ?? [];
    }

    private function generateDefaultEntityData(): array {
        $entities = [];

        $defaultEntities = [
            10 => ['name' => 'minecraft:chicken', 'width' => 0.4, 'height' => 0.7],
            11 => ['name' => 'minecraft:cow', 'width' => 0.9, 'height' => 1.4],
            12 => ['name' => 'minecraft:pig', 'width' => 0.9, 'height' => 0.9],
            13 => ['name' => 'minecraft:sheep', 'width' => 0.9, 'height' => 1.3],
            14 => ['name' => 'minecraft:wolf', 'width' => 0.6, 'height' => 0.85],
            15 => ['name' => 'minecraft:villager', 'width' => 0.6, 'height' => 1.95],
            32 => ['name' => 'minecraft:zombie', 'width' => 0.6, 'height' => 1.95],
            33 => ['name' => 'minecraft:creeper', 'width' => 0.6, 'height' => 1.7],
            34 => ['name' => 'minecraft:skeleton', 'width' => 0.6, 'height' => 1.99],
            35 => ['name' => 'minecraft:spider', 'width' => 1.4, 'height' => 0.9],
            36 => ['name' => 'minecraft:zombie_pigman', 'width' => 0.6, 'height' => 1.95],
            37 => ['name' => 'minecraft:slime', 'width' => 2.04, 'height' => 2.04],
            38 => ['name' => 'minecraft:enderman', 'width' => 0.6, 'height' => 2.9],
            39 => ['name' => 'minecraft:silverfish', 'width' => 0.4, 'height' => 0.3],
            40 => ['name' => 'minecraft:cave_spider', 'width' => 0.7, 'height' => 0.5],
            41 => ['name' => 'minecraft:ghast', 'width' => 4.0, 'height' => 4.0],
            42 => ['name' => 'minecraft:magma_cube', 'width' => 2.04, 'height' => 2.04],
            43 => ['name' => 'minecraft:blaze', 'width' => 0.6, 'height' => 1.8],
            44 => ['name' => 'minecraft:zombie_villager', 'width' => 0.6, 'height' => 1.95],
            45 => ['name' => 'minecraft:witch', 'width' => 0.6, 'height' => 1.95],
            46 => ['name' => 'minecraft:stray', 'width' => 0.6, 'height' => 1.99],
            47 => ['name' => 'minecraft:husk', 'width' => 0.6, 'height' => 1.95],
            48 => ['name' => 'minecraft:wither_skeleton', 'width' => 0.7, 'height' => 2.4],
            49 => ['name' => 'minecraft:guardian', 'width' => 0.85, 'height' => 0.85],
            50 => ['name' => 'minecraft:elder_guardian', 'width' => 1.9975, 'height' => 1.9975]
        ];

        foreach ($defaultEntities as $id => $data) {
            $entities[$id] = $data;
        }

        return $entities;
    }

    private function initializeMetadataTranslations(): void {
        $this->metadataTranslations = [
            0 => 'FLAGS',
            1 => 'HEALTH',
            2 => 'VARIANT',
            3 => 'COLOR',
            4 => 'NAMETAG',
            5 => 'OWNER',
            6 => 'TARGET',
            7 => 'AIR',
            8 => 'POTION_COLOR',
            9 => 'POTION_AMBIENT',
            10 => 'JUMP_DURATION',
            15 => 'SCALE',
            16 => 'INTERACTIVE_TAG',
            17 => 'TRADE_TIER',
            23 => 'ALWAYS_SHOW_NAMETAG',
            38 => 'BOUNDING_BOX_WIDTH',
            39 => 'BOUNDING_BOX_HEIGHT'
        ];
    }

    public function translate(int $entityId, int $fromProtocol, int $toProtocol): int {
        if ($fromProtocol === $toProtocol) {
            return $entityId;
        }

        $cacheKey = "{$entityId}_{$fromProtocol}_{$toProtocol}";

        if (isset($this->translationCache[$cacheKey])) {
            return $this->translationCache[$cacheKey];
        }

        $translated = $this->performTranslation($entityId, $fromProtocol, $toProtocol);

        $this->cacheTranslation($cacheKey, $translated);

        return $translated;
    }

    private function performTranslation(int $entityId, int $fromProtocol, int $toProtocol): int {
        $mappingKey = "{$fromProtocol}_{$toProtocol}";

        if (isset($this->mappings[$mappingKey][$entityId])) {
            return $this->mappings[$mappingKey][$entityId];
        }

        $fallback = $this->findFallbackEntity($entityId, $fromProtocol, $toProtocol);

        if ($fallback !== null) {
            return $fallback;
        }

        return 10;
    }

    private function findFallbackEntity(int $entityId, int $fromProtocol, int $toProtocol): ?int {
        $fromData = $this->getEntityData($entityId, $fromProtocol);

        if ($fromData === null) {
            return null;
        }

        $entityName = $fromData['name'] ?? null;

        if ($entityName === null) {
            return null;
        }

        $toData = $this->entityData[$toProtocol] ?? [];

        foreach ($toData as $id => $data) {
            if (($data['name'] ?? null) === $entityName) {
                return $id;
            }
        }

        return $this->getSimilarEntity($entityName, $toProtocol);
    }

    private function getSimilarEntity(string $entityName, int $protocol): ?int {
        $baseName = $this->extractBaseName($entityName);
        $toData = $this->entityData[$protocol] ?? [];

        foreach ($toData as $id => $data) {
            $dataName = $data['name'] ?? '';

            if ($this->extractBaseName($dataName) === $baseName) {
                return $id;
            }
        }

        return null;
    }

    private function extractBaseName(string $entityName): string {
        $parts = explode(':', $entityName);
        $name = $parts[1] ?? $entityName;

        $name = preg_replace('/_v\d+$/', '', $name);

        return $name;
    }

    private function getEntityData(int $entityId, int $protocol): ?array {
        return $this->entityData[$protocol][$entityId] ?? null;
    }

    private function cacheTranslation(string $cacheKey, int $translated): void {
        if ($this->cacheSize >= $this->maxCacheSize) {
            $this->cleanCache();
        }

        $this->translationCache[$cacheKey] = $translated;
        $this->cacheSize++;
    }

    private function cleanCache(): void {
        $this->translationCache = array_slice($this->translationCache, -2500, 2500, true);
        $this->cacheSize = count($this->translationCache);
    }

    public function translateEntity(array $entity, int $fromProtocol, int $toProtocol): array {
        $entityId = $entity['id'] ?? 10;
        $metadata = $entity['metadata'] ?? [];
        $attributes = $entity['attributes'] ?? [];

        $translatedId = $this->translate($entityId, $fromProtocol, $toProtocol);
        $translatedMetadata = $this->translateMetadata($metadata, $entityId, $fromProtocol, $toProtocol);
        $translatedAttributes = $this->translateAttributes($attributes, $entityId, $fromProtocol, $toProtocol);

        return [
            'id' => $translatedId,
            'metadata' => $translatedMetadata,
            'attributes' => $translatedAttributes,
            'name' => $this->getEntityName($translatedId, $toProtocol)
        ];
    }

    private function translateMetadata(array $metadata, int $entityId, int $fromProtocol, int $toProtocol): array {
        $translated = [];

        foreach ($metadata as $key => $value) {
            $translatedKey = $this->translateMetadataKey($key, $fromProtocol, $toProtocol);
            $translatedValue = $this->translateMetadataValue($key, $value, $fromProtocol, $toProtocol);

            if ($translatedKey !== null) {
                $translated[$translatedKey] = $translatedValue;
            }
        }

        return $translated;
    }

    private function translateMetadataKey(int $key, int $fromProtocol, int $toProtocol): ?int {
        return $key;
    }

    private function translateMetadataValue(int $key, mixed $value, int $fromProtocol, int $toProtocol): mixed {
        if ($key === 0) {
            return $this->translateEntityFlags($value, $fromProtocol, $toProtocol);
        }

        return $value;
    }

    private function translateEntityFlags(int $flags, int $fromProtocol, int $toProtocol): int {
        return $flags;
    }

    private function translateAttributes(array $attributes, int $entityId, int $fromProtocol, int $toProtocol): array {
        $translated = [];

        foreach ($attributes as $attribute) {
            $name = $attribute['name'] ?? '';
            $min = $attribute['min'] ?? 0.0;
            $max = $attribute['max'] ?? 0.0;
            $value = $attribute['value'] ?? 0.0;
            $default = $attribute['default'] ?? 0.0;

            $translatedName = $this->translateAttributeName($name, $fromProtocol, $toProtocol);

            $translated[] = [
                'name' => $translatedName,
                'min' => $min,
                'max' => $max,
                'value' => $value,
                'default' => $default
            ];
        }

        return $translated;
    }

    private function translateAttributeName(string $name, int $fromProtocol, int $toProtocol): string {
        return $name;
    }

    public function getEntityName(int $entityId, int $protocol): string {
        $data = $this->getEntityData($entityId, $protocol);
        return $data['name'] ?? 'minecraft:unknown';
    }

    public function getEntityId(string $entityName, int $protocol): ?int {
        $entities = $this->entityData[$protocol] ?? [];

        foreach ($entities as $id => $data) {
            if (($data['name'] ?? '') === $entityName) {
                return $id;
            }
        }

        return null;
    }

    public function translatePacket(object $packet, int $fromProtocol, int $toProtocol): object {
        $packetClass = get_class($packet);

        if (str_contains($packetClass, 'AddEntity')) {
            return $this->translateAddEntityPacket($packet, $fromProtocol, $toProtocol);
        }

        if (str_contains($packetClass, 'AddPlayer')) {
            return $this->translateAddPlayerPacket($packet, $fromProtocol, $toProtocol);
        }

        if (str_contains($packetClass, 'SetEntityData')) {
            return $this->translateSetEntityDataPacket($packet, $fromProtocol, $toProtocol);
        }

        if (str_contains($packetClass, 'UpdateAttributes')) {
            return $this->translateUpdateAttributesPacket($packet, $fromProtocol, $toProtocol);
        }

        return $packet;
    }

    private function translateAddEntityPacket(object $packet, int $fromProtocol, int $toProtocol): object {
        if (isset($packet->type)) {
            $packet->type = $this->translate($packet->type, $fromProtocol, $toProtocol);
        }

        if (isset($packet->metadata)) {
            $packet->metadata = $this->translateMetadata($packet->metadata, $packet->type ?? 10, $fromProtocol, $toProtocol);
        }

        return $packet;
    }

    private function translateAddPlayerPacket(object $packet, int $fromProtocol, int $toProtocol): object {
        if (isset($packet->metadata)) {
            $packet->metadata = $this->translateMetadata($packet->metadata, 63, $fromProtocol, $toProtocol);
        }

        return $packet;
    }

    private function translateSetEntityDataPacket(object $packet, int $fromProtocol, int $toProtocol): object {
        if (isset($packet->metadata)) {
            $entityId = 10;
            $packet->metadata = $this->translateMetadata($packet->metadata, $entityId, $fromProtocol, $toProtocol);
        }

        return $packet;
    }

    private function translateUpdateAttributesPacket(object $packet, int $fromProtocol, int $toProtocol): object {
        if (isset($packet->entries)) {
            $entityId = 10;
            $packet->entries = $this->translateAttributes($packet->entries, $entityId, $fromProtocol, $toProtocol);
        }

        return $packet;
    }

    public function addMapping(int $fromEntity, int $toEntity, int $fromProtocol, int $toProtocol): void {
        $mappingKey = "{$fromProtocol}_{$toProtocol}";

        if (!isset($this->mappings[$mappingKey])) {
            $this->mappings[$mappingKey] = [];
        }

        $this->mappings[$mappingKey][$fromEntity] = $toEntity;

        $reverseMappingKey = "{$toProtocol}_{$fromProtocol}";
        if (!isset($this->reverseMappings[$reverseMappingKey])) {
            $this->reverseMappings[$reverseMappingKey] = [];
        }
        $this->reverseMappings[$reverseMappingKey][$toEntity] = $fromEntity;
    }

    public function hasMapping(int $entityId, int $fromProtocol, int $toProtocol): bool {
        $mappingKey = "{$fromProtocol}_{$toProtocol}";
        return isset($this->mappings[$mappingKey][$entityId]);
    }

    public function clearCache(): void {
        $this->translationCache = [];
        $this->cacheSize = 0;
    }

    public function getStatistics(): array {
        return [
            'cache_size' => $this->cacheSize,
            'mappings_loaded' => count($this->mappings),
            'entities_loaded' => array_sum(array_map('count', $this->entityData))
        ];
    }

    public function saveMappings(): void {
        $dataPath = MultiVersion::getInstance()->getDataFolder() . "data/mappings/entities/";

        if (!is_dir($dataPath)) {
            mkdir($dataPath, 0777, true);
        }

        foreach ($this->mappings as $key => $mapping) {
            $file = $dataPath . "{$key}.json";
            file_put_contents($file, json_encode($mapping, JSON_PRETTY_PRINT));
        }
    }
}