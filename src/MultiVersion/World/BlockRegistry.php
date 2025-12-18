<?php
declare(strict_types=1);

namespace MultiVersion\World;

use MultiVersion\MultiVersion;

final class BlockRegistry {

    private MultiVersion $plugin;
    private array $blocks = [];
    private array $blocksByName = [];
    private array $runtimeIdMap = [];
    private array $protocolMappings = [];

    public function __construct(MultiVersion $plugin) {
        $this->plugin = $plugin;
        $this->registerVanillaBlocks();
        $this->loadProtocolMappings();
    }

    private function registerVanillaBlocks(): void {
        $vanillaBlocks = [
            0 => 'air',
            1 => 'stone',
            2 => 'grass',
            3 => 'dirt',
            4 => 'cobblestone',
            5 => 'planks',
            6 => 'sapling',
            7 => 'bedrock',
            8 => 'flowing_water',
            9 => 'water',
            10 => 'flowing_lava',
            11 => 'lava',
            12 => 'sand',
            13 => 'gravel',
            14 => 'gold_ore',
            15 => 'iron_ore',
            16 => 'coal_ore',
            17 => 'log',
            18 => 'leaves',
            19 => 'sponge',
            20 => 'glass',
            21 => 'lapis_ore',
            22 => 'lapis_block',
            23 => 'dispenser',
            24 => 'sandstone',
            25 => 'noteblock',
            26 => 'bed',
            27 => 'golden_rail',
            28 => 'detector_rail',
            29 => 'sticky_piston',
            30 => 'web',
            31 => 'tallgrass',
            32 => 'deadbush',
            33 => 'piston',
            35 => 'wool',
            41 => 'gold_block',
            42 => 'iron_block',
            43 => 'double_stone_slab',
            44 => 'stone_slab',
            45 => 'brick_block',
            46 => 'tnt',
            47 => 'bookshelf',
            48 => 'mossy_cobblestone',
            49 => 'obsidian',
            50 => 'torch',
            51 => 'fire',
            52 => 'mob_spawner',
            53 => 'oak_stairs',
            54 => 'chest',
            56 => 'diamond_ore',
            57 => 'diamond_block',
            58 => 'crafting_table',
            60 => 'farmland',
            61 => 'furnace',
            62 => 'lit_furnace',
            63 => 'standing_sign',
            64 => 'wooden_door',
            65 => 'ladder',
            66 => 'rail',
            67 => 'stone_stairs',
            68 => 'wall_sign',
            69 => 'lever',
            70 => 'stone_pressure_plate',
            71 => 'iron_door',
            72 => 'wooden_pressure_plate',
            73 => 'redstone_ore',
            74 => 'lit_redstone_ore',
            79 => 'ice',
            80 => 'snow',
            81 => 'cactus',
            82 => 'clay',
            83 => 'reeds',
            84 => 'jukebox',
            85 => 'fence',
            86 => 'pumpkin',
            87 => 'netherrack',
            88 => 'soul_sand',
            89 => 'glowstone',
            90 => 'portal',
            91 => 'lit_pumpkin',
            95 => 'stained_glass',
            98 => 'stonebrick'
        ];

        foreach ($vanillaBlocks as $id => $name) {
            $this->registerBlock($id, $name);
        }

        $this->generateRuntimeIds();
    }

    private function generateRuntimeIds(): void {
        $runtimeId = 0;
        foreach ($this->blocks as $id => $name) {
            for ($meta = 0; $meta < 16; $meta++) {
                $fullId = ($id << 4) | $meta;
                $this->runtimeIdMap[$fullId] = $runtimeId;
                $runtimeId++;
            }
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
        $mappingFile = $this->plugin->getDataFolder() . "data/mappings/blocks/{$protocol}.json";

        if (file_exists($mappingFile)) {
            $json = file_get_contents($mappingFile);
            $data = json_decode($json, true);
            return $data ?? [];
        }

        return array_keys($this->blocks);
    }

    public function registerBlock(int $id, string $name): void {
        $this->blocks[$id] = $name;
        $this->blocksByName[$name] = $id;
    }

    public function unregisterBlock(int $id): void {
        if (isset($this->blocks[$id])) {
            $name = $this->blocks[$id];
            unset($this->blocks[$id]);
            unset($this->blocksByName[$name]);
        }
    }

    public function getBlock(int $id): ?string {
        return $this->blocks[$id] ?? null;
    }

    public function getBlockByName(string $name): ?int {
        return $this->blocksByName[$name] ?? null;
    }

    public function isBlockRegistered(int $id): bool {
        return isset($this->blocks[$id]);
    }

    public function getRuntimeId(int $id, int $meta = 0): int {
        $fullId = ($id << 4) | ($meta & 0x0f);
        return $this->runtimeIdMap[$fullId] ?? 0;
    }

    public function getBlockFromRuntimeId(int $runtimeId): Block {
        $fullId = array_search($runtimeId, $this->runtimeIdMap, true);

        if ($fullId === false) {
            return Block::air();
        }

        $id = $fullId >> 4;
        $meta = $fullId & 0x0f;

        return new Block($id, $meta, $runtimeId);
    }

    public function getIdFromRuntimeId(int $runtimeId): int {
        $fullId = array_search($runtimeId, $this->runtimeIdMap, true);
        return $fullId !== false ? ($fullId >> 4) : 0;
    }

    public function getMetaFromRuntimeId(int $runtimeId): int {
        $fullId = array_search($runtimeId, $this->runtimeIdMap, true);
        return $fullId !== false ? ($fullId & 0x0f) : 0;
    }

    public function getAllBlocks(): array {
        return $this->blocks;
    }

    public function getBlockCount(): int {
        return count($this->blocks);
    }

    public function isBlockSupportedInProtocol(int $blockId, int $protocol): bool {
        $mapping = $this->protocolMappings[$protocol] ?? null;
        if ($mapping === null) {
            return false;
        }

        return in_array($blockId, $mapping, true);
    }

    public function getProtocolBlocks(int $protocol): array {
        return $this->protocolMappings[$protocol] ?? [];
    }

    public function translateBlockId(int $blockId, int $fromProtocol, int $toProtocol): int {
        if ($fromProtocol === $toProtocol) {
            return $blockId;
        }

        return $blockId;
    }

    public function searchBlocks(string $query): array {
        $results = [];
        $query = strtolower($query);

        foreach ($this->blocks as $id => $name) {
            if (str_contains(strtolower($name), $query)) {
                $results[$id] = $name;
            }
        }

        return $results;
    }
}