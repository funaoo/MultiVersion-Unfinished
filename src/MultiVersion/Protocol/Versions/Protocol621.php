<?php

declare(strict_types=1);

namespace MultiVersion\Protocol\Versions;

use MultiVersion\Protocol\ProtocolInterface;
use pocketmine\network\mcpe\protocol\DataPacket;

final class Protocol621 implements ProtocolInterface{

    private int $protocolVersion = 621;
    private string $minecraftVersion = '1.21.130';
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
            'trial_chambers' => true,
            'crafter' => true,
            'copper_family' => true,
            'armadillo' => true,
            'wolf_variants' => true,
            'breeze' => true,
            'trial_spawner' => true,
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
            'disconnect' => 0x05,
            'resource_packs_info' => 0x06,
            'text' => 0x09,
            'start_game' => 0x0b,
            'add_player' => 0x0c,
            'add_entity' => 0x0d,
            'remove_entity' => 0x0e,
            'move_player' => 0x13,
            'player_action' => 0x24,
            'animate' => 0x2c,
            'respawn' => 0x2d,
            'level_chunk' => 0x3a
        ];
    }

    private function loadPalettes(): void{
        $this->blockPalette = range(0, 2000);
        $this->itemPalette = range(1, 1500);
        $this->entityIds = range(10, 200);
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
        return $version >= 40;
    }

    public function getChunkVersion(): int{
        return 40;
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
