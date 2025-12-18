<?php
declare(strict_types=1);

namespace MultiVersion\Item;

use MultiVersion\MultiVersion;

final class ItemRegistry {

    private MultiVersion $plugin;
    private array $items = [];
    private array $itemsByName = [];
    private array $protocolMappings = [];

    public function __construct(MultiVersion $plugin) {
        $this->plugin = $plugin;
        $this->loadItems();
        $this->loadProtocolMappings();
    }

    private function loadItems(): void {
        $this->registerVanillaItems();
    }

    private function registerVanillaItems(): void {
        $vanillaItems = [
            1 => ['name' => 'stone', 'max_stack' => 64],
            2 => ['name' => 'grass', 'max_stack' => 64],
            3 => ['name' => 'dirt', 'max_stack' => 64],
            4 => ['name' => 'cobblestone', 'max_stack' => 64],
            5 => ['name' => 'planks', 'max_stack' => 64],
            6 => ['name' => 'sapling', 'max_stack' => 64],
            7 => ['name' => 'bedrock', 'max_stack' => 64],
            8 => ['name' => 'flowing_water', 'max_stack' => 64],
            9 => ['name' => 'water', 'max_stack' => 64],
            10 => ['name' => 'flowing_lava', 'max_stack' => 64],
            11 => ['name' => 'lava', 'max_stack' => 64],
            12 => ['name' => 'sand', 'max_stack' => 64],
            13 => ['name' => 'gravel', 'max_stack' => 64],
            14 => ['name' => 'gold_ore', 'max_stack' => 64],
            15 => ['name' => 'iron_ore', 'max_stack' => 64],
            16 => ['name' => 'coal_ore', 'max_stack' => 64],
            17 => ['name' => 'log', 'max_stack' => 64],
            18 => ['name' => 'leaves', 'max_stack' => 64],
            256 => ['name' => 'iron_shovel', 'max_stack' => 1],
            257 => ['name' => 'iron_pickaxe', 'max_stack' => 1],
            258 => ['name' => 'iron_axe', 'max_stack' => 1],
            259 => ['name' => 'flint_and_steel', 'max_stack' => 1],
            260 => ['name' => 'apple', 'max_stack' => 64],
            261 => ['name' => 'bow', 'max_stack' => 1],
            262 => ['name' => 'arrow', 'max_stack' => 64],
            263 => ['name' => 'coal', 'max_stack' => 64],
            264 => ['name' => 'diamond', 'max_stack' => 64],
            265 => ['name' => 'iron_ingot', 'max_stack' => 64],
            266 => ['name' => 'gold_ingot', 'max_stack' => 64],
            267 => ['name' => 'iron_sword', 'max_stack' => 1],
            268 => ['name' => 'wooden_sword', 'max_stack' => 1],
            269 => ['name' => 'wooden_shovel', 'max_stack' => 1],
            270 => ['name' => 'wooden_pickaxe', 'max_stack' => 1],
            271 => ['name' => 'wooden_axe', 'max_stack' => 1],
            272 => ['name' => 'stone_sword', 'max_stack' => 1],
            273 => ['name' => 'stone_shovel', 'max_stack' => 1],
            274 => ['name' => 'stone_pickaxe', 'max_stack' => 1],
            275 => ['name' => 'stone_axe', 'max_stack' => 1],
            276 => ['name' => 'diamond_sword', 'max_stack' => 1],
            277 => ['name' => 'diamond_shovel', 'max_stack' => 1],
            278 => ['name' => 'diamond_pickaxe', 'max_stack' => 1],
            279 => ['name' => 'diamond_axe', 'max_stack' => 1],
            280 => ['name' => 'stick', 'max_stack' => 64],
            281 => ['name' => 'bowl', 'max_stack' => 64],
            282 => ['name' => 'mushroom_stew', 'max_stack' => 1],
            283 => ['name' => 'golden_sword', 'max_stack' => 1],
            284 => ['name' => 'golden_shovel', 'max_stack' => 1],
            285 => ['name' => 'golden_pickaxe', 'max_stack' => 1],
            286 => ['name' => 'golden_axe', 'max_stack' => 1]
        ];

        foreach ($vanillaItems as $id => $data) {
            $this->items[$id] = $data;
            $this->itemsByName[$data['name']] = $id;
        }
    }

    private function loadProtocolMappings(): void {
        $this->protocolMappings = [
            621 => $this->loadProtocolMapping(621),
            594 => $this->loadProtocolMapping(594),
            527 => $this->loadProtocolMapping(527)
        ];
    }

    private function loadProtocolMapping(int $protocol): array {
        $mappingFile = $this->plugin->getDataFolder() . "data/mappings/items/{$protocol}.json";

        if (file_exists($mappingFile)) {
            $json = file_get_contents($mappingFile);
            $data = json_decode($json, true);
            return $data ?? [];
        }

        return array_keys($this->items);
    }

    public function getItem(int $id): ?array {
        return $this->items[$id] ?? null;
    }

    public function getItemByName(string $name): ?int {
        return $this->itemsByName[$name] ?? null;
    }

    public function isItemRegistered(int $id): bool {
        return isset($this->items[$id]);
    }

    public function registerItem(int $id, string $name, int $maxStack = 64): void {
        $this->items[$id] = [
            'name' => $name,
            'max_stack' => $maxStack
        ];
        $this->itemsByName[$name] = $id;
    }

    public function unregisterItem(int $id): void {
        if (isset($this->items[$id])) {
            $name = $this->items[$id]['name'];
            unset($this->items[$id]);
            unset($this->itemsByName[$name]);
        }
    }

    public function getMaxStackSize(int $id): int {
        return $this->items[$id]['max_stack'] ?? 64;
    }

    public function getItemName(int $id): ?string {
        return $this->items[$id]['name'] ?? null;
    }

    public function getAllItems(): array {
        return $this->items;
    }

    public function getItemCount(): int {
        return count($this->items);
    }

    public function translateItemId(int $itemId, int $fromProtocol, int $toProtocol): int {
        if ($fromProtocol === $toProtocol) {
            return $itemId;
        }

        $fromMapping = $this->protocolMappings[$fromProtocol] ?? null;
        $toMapping = $this->protocolMappings[$toProtocol] ?? null;

        if ($fromMapping === null || $toMapping === null) {
            return $itemId;
        }

        $index = array_search($itemId, $fromMapping, true);
        if ($index === false) {
            return $itemId;
        }

        return $toMapping[$index] ?? $itemId;
    }

    public function isItemSupportedInProtocol(int $itemId, int $protocol): bool {
        $mapping = $this->protocolMappings[$protocol] ?? null;
        if ($mapping === null) {
            return false;
        }

        return in_array($itemId, $mapping, true);
    }

    public function getProtocolItems(int $protocol): array {
        return $this->protocolMappings[$protocol] ?? [];
    }

    public function searchItems(string $query): array {
        $results = [];
        $query = strtolower($query);

        foreach ($this->items as $id => $data) {
            if (str_contains(strtolower($data['name']), $query)) {
                $results[$id] = $data;
            }
        }

        return $results;
    }
}