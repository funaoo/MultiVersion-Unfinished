<?php

declare(strict_types=1);

namespace MultiVersion\Translator;

use MultiVersion\MultiVersion;

final class ItemTranslator {

    private array $mappings = [];
    private array $reverseMappings = [];
    private array $itemData = [];
    private array $translationCache = [];
    private array $nbtTranslations = [];
    private int $cacheSize = 0;
    private int $maxCacheSize = 10000;

    public function __construct() {
        $this->loadMappings();
        $this->loadItemData();
        $this->initializeNBTTranslations();
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
        $dataPath = MultiVersion::getInstance()->getDataFolder() . "data/mappings/items/";
        $file = $dataPath . "{$from}_to_{$to}.json";

        if (!file_exists($file)) {
            return $this->generateDefaultMapping($from, $to);
        }

        $content = file_get_contents($file);
        return json_decode($content, true) ?? [];
    }

    private function generateDefaultMapping(int $from, int $to): array {
        $mapping = [];

        $commonItems = [
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
            256 => 256,
            257 => 257,
            258 => 258,
            259 => 259,
            260 => 260,
            261 => 261,
            262 => 262,
            263 => 263,
            264 => 264,
            265 => 265,
            266 => 266,
            267 => 267,
            268 => 268,
            269 => 269,
            270 => 270,
            271 => 271,
            272 => 272
        ];

        foreach ($commonItems as $fromItem => $toItem) {
            $mapping[$fromItem] = $toItem;
        }

        return $mapping;
    }

    private function loadItemData(): void {
        $protocols = [621, 594, 527];

        foreach ($protocols as $protocol) {
            $this->itemData[$protocol] = $this->loadItemDataFile($protocol);
        }
    }

    private function loadItemDataFile(int $protocol): array {
        $dataPath = MultiVersion::getInstance()->getDataFolder() . "data/protocol/{$protocol}/";
        $file = $dataPath . "items.json";

        if (!file_exists($file)) {
            return $this->generateDefaultItemData();
        }

        $content = file_get_contents($file);
        return json_decode($content, true) ?? [];
    }

    private function generateDefaultItemData(): array {
        $items = [];

        for ($i = 1; $i < 1000; $i++) {
            $items[$i] = [
                'name' => 'minecraft:unknown',
                'id' => $i,
                'max_stack' => 64,
                'max_damage' => 0
            ];
        }

        return $items;
    }

    private function initializeNBTTranslations(): void {
        $this->nbtTranslations = [
            'display' => true,
            'ench' => true,
            'Damage' => true,
            'Unbreakable' => true,
            'CanDestroy' => true,
            'CanPlaceOn' => true,
            'CustomName' => true,
            'Lore' => true
        ];
    }

    public function translate(int $itemId, int $fromProtocol, int $toProtocol): int {
        if ($fromProtocol === $toProtocol) {
            return $itemId;
        }

        $cacheKey = "{$itemId}_{$fromProtocol}_{$toProtocol}";

        if (isset($this->translationCache[$cacheKey])) {
            return $this->translationCache[$cacheKey];
        }

        $translated = $this->performTranslation($itemId, $fromProtocol, $toProtocol);

        $this->cacheTranslation($cacheKey, $translated);

        return $translated;
    }

    private function performTranslation(int $itemId, int $fromProtocol, int $toProtocol): int {
        $mappingKey = "{$fromProtocol}_{$toProtocol}";

        if (isset($this->mappings[$mappingKey][$itemId])) {
            return $this->mappings[$mappingKey][$itemId];
        }

        $fallback = $this->findFallbackItem($itemId, $fromProtocol, $toProtocol);

        if ($fallback !== null) {
            return $fallback;
        }

        return 1;
    }

    private function findFallbackItem(int $itemId, int $fromProtocol, int $toProtocol): ?int {
        $fromData = $this->getItemData($itemId, $fromProtocol);

        if ($fromData === null) {
            return null;
        }

        $itemName = $fromData['name'] ?? null;

        if ($itemName === null) {
            return null;
        }

        $toData = $this->itemData[$toProtocol] ?? [];

        foreach ($toData as $id => $data) {
            if (($data['name'] ?? null) === $itemName) {
                return $id;
            }
        }

        return $this->getSimilarItem($itemName, $toProtocol);
    }

    private function getSimilarItem(string $itemName, int $protocol): ?int {
        $baseName = $this->extractBaseName($itemName);
        $toData = $this->itemData[$protocol] ?? [];

        foreach ($toData as $id => $data) {
            $dataName = $data['name'] ?? '';

            if ($this->extractBaseName($dataName) === $baseName) {
                return $id;
            }
        }

        return null;
    }

    private function extractBaseName(string $itemName): string {
        $parts = explode(':', $itemName);
        $name = $parts[1] ?? $itemName;

        $name = preg_replace('/_\d+$/', '', $name);

        return $name;
    }

    private function getItemData(int $itemId, int $protocol): ?array {
        return $this->itemData[$protocol][$itemId] ?? null;
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

    public function translateItem(array $item, int $fromProtocol, int $toProtocol): array {
        $itemId = $item['id'] ?? 1;
        $itemMeta = $item['meta'] ?? 0;
        $itemCount = $item['count'] ?? 1;
        $itemNBT = $item['nbt'] ?? null;

        $translatedId = $this->translate($itemId, $fromProtocol, $toProtocol);
        $translatedMeta = $this->translateMeta($itemId, $itemMeta, $fromProtocol, $toProtocol);
        $translatedNBT = $this->translateNBT($itemNBT, $itemId, $fromProtocol, $toProtocol);

        return [
            'id' => $translatedId,
            'meta' => $translatedMeta,
            'count' => $itemCount,
            'nbt' => $translatedNBT,
            'name' => $this->getItemName($translatedId, $toProtocol)
        ];
    }

    private function translateMeta(int $itemId, int $meta, int $fromProtocol, int $toProtocol): int {
        $fromData = $this->getItemData($itemId, $fromProtocol);

        if ($fromData === null) {
            return $meta;
        }

        $hasDurability = ($fromData['max_damage'] ?? 0) > 0;

        if ($hasDurability) {
            return $meta;
        }

        return $this->convertMetaValue($meta, $itemId, $fromProtocol, $toProtocol);
    }

    private function convertMetaValue(int $meta, int $itemId, int $fromProtocol, int $toProtocol): int {
        return $meta;
    }

    private function translateNBT(?array $nbt, int $itemId, int $fromProtocol, int $toProtocol): ?array {
        if ($nbt === null || empty($nbt)) {
            return null;
        }

        $translated = [];

        foreach ($nbt as $key => $value) {
            if (isset($this->nbtTranslations[$key])) {
                $translatedKey = $this->translateNBTKey($key, $fromProtocol, $toProtocol);
                $translatedValue = $this->translateNBTValue($key, $value, $fromProtocol, $toProtocol);
                $translated[$translatedKey] = $translatedValue;
            } else {
                $translated[$key] = $value;
            }
        }

        return $translated;
    }

    private function translateNBTKey(string $key, int $fromProtocol, int $toProtocol): string {
        return $key;
    }

    private function translateNBTValue(string $key, mixed $value, int $fromProtocol, int $toProtocol): mixed {
        if ($key === 'ench' && is_array($value)) {
            return $this->translateEnchantments($value, $fromProtocol, $toProtocol);
        }

        if ($key === 'CanDestroy' && is_array($value)) {
            return $this->translateBlockList($value, $fromProtocol, $toProtocol);
        }

        if ($key === 'CanPlaceOn' && is_array($value)) {
            return $this->translateBlockList($value, $fromProtocol, $toProtocol);
        }

        return $value;
    }

    private function translateEnchantments(array $enchantments, int $fromProtocol, int $toProtocol): array {
        $translated = [];

        foreach ($enchantments as $enchant) {
            $id = $enchant['id'] ?? 0;
            $lvl = $enchant['lvl'] ?? 1;

            $translatedId = $this->translateEnchantmentId($id, $fromProtocol, $toProtocol);

            $translated[] = [
                'id' => $translatedId,
                'lvl' => $lvl
            ];
        }

        return $translated;
    }

    private function translateEnchantmentId(int $enchantId, int $fromProtocol, int $toProtocol): int {
        return $enchantId;
    }

    private function translateBlockList(array $blocks, int $fromProtocol, int $toProtocol): array {
        $translator = new BlockTranslator();
        $translated = [];

        foreach ($blocks as $blockName) {
            $translated[] = $blockName;
        }

        return $translated;
    }

    public function getItemName(int $itemId, int $protocol): string {
        $data = $this->getItemData($itemId, $protocol);
        return $data['name'] ?? 'minecraft:air';
    }

    public function getItemId(string $itemName, int $protocol): ?int {
        $items = $this->itemData[$protocol] ?? [];

        foreach ($items as $id => $data) {
            if (($data['name'] ?? '') === $itemName) {
                return $id;
            }
        }

        return null;
    }

    public function translatePacket(object $packet, int $fromProtocol, int $toProtocol): object {
        $packetClass = get_class($packet);

        if (str_contains($packetClass, 'MobEquipment')) {
            return $this->translateMobEquipmentPacket($packet, $fromProtocol, $toProtocol);
        }

        if (str_contains($packetClass, 'InventorySlot')) {
            return $this->translateInventorySlotPacket($packet, $fromProtocol, $toProtocol);
        }

        if (str_contains($packetClass, 'InventoryContent')) {
            return $this->translateInventoryContentPacket($packet, $fromProtocol, $toProtocol);
        }

        return $packet;
    }

    private function translateMobEquipmentPacket(object $packet, int $fromProtocol, int $toProtocol): object {
        if (isset($packet->item)) {
            $packet->item = $this->translateItemStack($packet->item, $fromProtocol, $toProtocol);
        }

        return $packet;
    }

    private function translateInventorySlotPacket(object $packet, int $fromProtocol, int $toProtocol): object {
        if (isset($packet->item)) {
            $packet->item = $this->translateItemStack($packet->item, $fromProtocol, $toProtocol);
        }

        return $packet;
    }

    private function translateInventoryContentPacket(object $packet, int $fromProtocol, int $toProtocol): object {
        if (isset($packet->items) && is_array($packet->items)) {
            foreach ($packet->items as $key => $item) {
                $packet->items[$key] = $this->translateItemStack($item, $fromProtocol, $toProtocol);
            }
        }

        return $packet;
    }

    private function translateItemStack(object $itemStack, int $fromProtocol, int $toProtocol): object {
        $id = method_exists($itemStack, 'getId') ? $itemStack->getId() : 0;
        $meta = method_exists($itemStack, 'getMeta') ? $itemStack->getMeta() : 0;

        $translatedId = $this->translate($id, $fromProtocol, $toProtocol);
        $translatedMeta = $this->translateMeta($id, $meta, $fromProtocol, $toProtocol);

        if (method_exists($itemStack, 'setId')) {
            $itemStack->setId($translatedId);
        }

        if (method_exists($itemStack, 'setMeta')) {
            $itemStack->setMeta($translatedMeta);
        }

        return $itemStack;
    }

    public function addMapping(int $fromItem, int $toItem, int $fromProtocol, int $toProtocol): void {
        $mappingKey = "{$fromProtocol}_{$toProtocol}";

        if (!isset($this->mappings[$mappingKey])) {
            $this->mappings[$mappingKey] = [];
        }

        $this->mappings[$mappingKey][$fromItem] = $toItem;

        $reverseMappingKey = "{$toProtocol}_{$fromProtocol}";
        if (!isset($this->reverseMappings[$reverseMappingKey])) {
            $this->reverseMappings[$reverseMappingKey] = [];
        }
        $this->reverseMappings[$reverseMappingKey][$toItem] = $fromItem;
    }

    public function hasMapping(int $itemId, int $fromProtocol, int $toProtocol): bool {
        $mappingKey = "{$fromProtocol}_{$toProtocol}";
        return isset($this->mappings[$mappingKey][$itemId]);
    }

    public function clearCache(): void {
        $this->translationCache = [];
        $this->cacheSize = 0;
    }

    public function getStatistics(): array {
        return [
            'cache_size' => $this->cacheSize,
            'mappings_loaded' => count($this->mappings),
            'items_loaded' => array_sum(array_map('count', $this->itemData))
        ];
    }

    public function saveMappings(): void {
        $dataPath = MultiVersion::getInstance()->getDataFolder() . "data/mappings/items/";

        if (!is_dir($dataPath)) {
            mkdir($dataPath, 0777, true);
        }

        foreach ($this->mappings as $key => $mapping) {
            $file = $dataPath . "{$key}.json";
            file_put_contents($file, json_encode($mapping, JSON_PRETTY_PRINT));
        }
    }
}