<?php

declare(strict_types=1);

namespace MultiVersion\Protocol\Versions;

use MultiVersion\Protocol\ProtocolInterface;
use pocketmine\network\mcpe\protocol\DataPacket;

final class Protocol594 implements ProtocolInterface{

    private int $protocolVersion = 594;
    private string $minecraftVersion = '1.20.40';
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
            'deep_dark' => true,
            'ancient_cities' => true,
            'warden' => true,
            'mangrove_swamp' => true,
            'mud' => true,
            'frogs' => true,
            'allays' => true,
            'trial_chambers' => false,
            'crafter' => false,
            'copper_family' => true,
            'armadillo' => false,
            'wolf_variants' => false,
            'breeze' => false,
            'trial_spawner' => false,
            'decorated_pot' => true,
            'cherry_grove' => true,
            'bamboo_blocks' => true,
            'hanging_signs' => true,
            'chiseled_bookshelf' => true,
            'camel' => true,
            'sniffer' => true,
            'smithing_templates' => true
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
            'level_event' => 0x19,
            'entity_event' => 0x1b,
            'mob_effect' => 0x1c,
            'inventory_transaction' => 0x1e,
            'player_action' => 0x24,
            'animate' => 0x2c,
            'respawn' => 0x2d,
            'level_chunk' => 0x3a
        ];
    }

    private function loadPalettes(): void{
        $this->blockPalette = range(0, 1800);
        $this->itemPalette = range(1, 1400);
        $this->entityIds = range(10, 180);
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
        return $version >= 38 && $version <= 40;
    }

    public function getChunkVersion(): int{
        return 39;
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
