<?php

declare(strict_types=1);

namespace MultiVersion\Protocol\Versions;

use MultiVersion\Protocol\ProtocolInterface;
use pocketmine\network\mcpe\protocol\DataPacket;

final class Protocol527 implements ProtocolInterface{

    private int $protocolVersion = 527;
    private string $minecraftVersion = '1.18.12';
    private array $features = [];
    private array $packetIds = [];
    private array $blockPalette = [];
    private array $itemPalette = [];
    private array $entityIds = [];

    public function __construct(){
        $this->loadFeatures();
        $this->loadPacketIds();
        $this->loadPalettes();
    }

    private function loadFeatures(): void{
        $this->features = [
            'deep_dark' => false,
            'ancient_cities' => false,
            'warden' => false,
            'mangrove_swamp' => false,
            'mud' => false,
            'frogs' => false,
            'allays' => false,
            'trial_chambers' => false,
            'crafter' => false,
            'copper_family' => true,
            'armadillo' => false,
            'wolf_variants' => false,
            'breeze' => false,
            'trial_spawner' => false,
            'decorated_pot' => false,
            'cherry_grove' => false,
            'bamboo_blocks' => false,
            'hanging_signs' => false,
            'chiseled_bookshelf' => false,
            'camel' => false,
            'sniffer' => false,
            'smithing_templates' => false,
            'caves_cliffs' => true,
            'world_height_384' => true,
            'new_ore_distribution' => true,
            'dripstone' => true,
            'lush_caves' => true,
            'azalea' => true,
            'glow_squid' => true,
            'axolotl' => true,
            'goat' => true
        ];
    }

    private function loadPacketIds(): void{
        $this->packetIds = [
            'login' => 0x01,
            'play_status' => 0x02,
            'server_to_client_handshake' => 0x03,
            'client_to_server_handshake' => 0x04,
            'disconnect' => 0x05,
            'resource_packs_info' => 0x06,
            'resource_pack_stack' => 0x07,
            'resource_pack_client_response' => 0x08,
            'text' => 0x09,
            'set_time' => 0x0a,
            'start_game' => 0x0b,
            'add_player' => 0x0c,
            'add_entity' => 0x0d,
            'remove_entity' => 0x0e,
            'add_item_entity' => 0x0f,
            'take_item_entity' => 0x11,
            'move_entity_absolute' => 0x12,
            'move_player' => 0x13,
            'rider_jump' => 0x14,
            'update_block' => 0x15,
            'add_painting' => 0x16,
            'level_sound_event_v1' => 0x18,
            'level_event' => 0x19,
            'block_event' => 0x1a,
            'entity_event' => 0x1b,
            'mob_effect' => 0x1c,
            'update_attributes' => 0x1d,
            'inventory_transaction' => 0x1e,
            'mob_equipment' => 0x1f,
            'mob_armor_equipment' => 0x20,
            'interact' => 0x21,
            'block_pick_request' => 0x22,
            'entity_pick_request' => 0x23,
            'player_action' => 0x24,
            'hurt_armor' => 0x26,
            'set_entity_data' => 0x27,
            'set_entity_motion' => 0x28,
            'set_entity_link' => 0x29,
            'set_health' => 0x2a,
            'set_spawn_position' => 0x2b,
            'animate' => 0x2c,
            'respawn' => 0x2d,
            'container_open' => 0x2e,
            'container_close' => 0x2f,
            'player_hotbar' => 0x30,
            'inventory_content' => 0x31,
            'inventory_slot' => 0x32,
            'container_set_data' => 0x33,
            'crafting_data' => 0x34,
            'crafting_event' => 0x35,
            'gui_data_pick_item' => 0x36,
            'adventure_settings' => 0x37,
            'block_entity_data' => 0x38,
            'player_input' => 0x39,
            'level_chunk' => 0x3a
        ];
    }

    private function loadPalettes(): void{
        $this->blockPalette = range(0, 1500);
        $this->itemPalette = range(1, 1200);
        $this->entityIds = range(10, 150);
    }

    public function getProtocolVersion(): int{
        return $this->protocolVersion;
    }

    public function getMinecraftVersion(): string{
        return $this->minecraftVersion;
    }

    public function getNetworkVersion(): string{
        return $this->minecraftVersion;
    }

    public function getPacketId(string $packetName): ?int{
        return $this->packetIds[$packetName] ?? null;
    }

    public function getPacketName(int $packetId): ?string{
        $flip = array_flip($this->packetIds);
        return $flip[$packetId] ?? null;
    }

    public function hasFeature(string $feature): bool{
        return $this->features[$feature] ?? false;
    }

    public function getFeatures(): array{
        return $this->features;
    }

    public function getBlockRuntimeId(int $blockId, int $blockData = 0): int{
        return ($blockId << 6) | ($blockData & 0x3f);
    }

    public function getBlockIdFromRuntimeId(int $runtimeId): int{
        return $runtimeId >> 6;
    }

    public function getBlockDataFromRuntimeId(int $runtimeId): int{
        return $runtimeId & 0x3f;
    }

    public function isBlockSupported(int $blockId): bool{
        return in_array($blockId, $this->blockPalette, true);
    }

    public function isItemSupported(int $itemId): bool{
        return in_array($itemId, $this->itemPalette, true);
    }

    public function isEntitySupported(int $entityId): bool{
        return in_array($entityId, $this->entityIds, true);
    }

    public function getMaxBlockId(): int{
        return max($this->blockPalette);
    }

    public function getMaxItemId(): int{
        return max($this->itemPalette);
    }

    public function getMaxEntityId(): int{
        return max($this->entityIds);
    }

    public function translatePacket(DataPacket $packet, int $targetProtocol): DataPacket{
        return $packet;
    }

    public function supportsChunkVersion(int $version): bool{
        return $version >= 35 && $version <= 38;
    }

    public function getChunkVersion(): int{
        return 37;
    }

    public function supportsEncryption(): bool{
        return true;
    }

    public function supportsCompression(): bool{
        return true;
    }

    public function getCompressionThreshold(): int{
        return 256;
    }
}
