<?php

declare(strict_types=1);

namespace MultiVersion\Protocol;

use pocketmine\network\mcpe\protocol\DataPacket;

interface ProtocolInterface{

    public function getProtocolVersion(): int;

    public function getMinecraftVersion(): string;

    public function getNetworkVersion(): string;

    public function getPacketId(string $packetName): ?int;

    public function getPacketName(int $packetId): ?string;

    public function hasFeature(string $feature): bool;

    public function getFeatures(): array;

    public function getBlockRuntimeId(int $blockId, int $blockData = 0): int;

    public function getBlockIdFromRuntimeId(int $runtimeId): int;

    public function getBlockDataFromRuntimeId(int $runtimeId): int;

    public function isBlockSupported(int $blockId): bool;

    public function isItemSupported(int $itemId): bool;

    public function isEntitySupported(int $entityId): bool;

    public function getMaxBlockId(): int;

    public function getMaxItemId(): int;

    public function getMaxEntityId(): int;

    public function translatePacket(DataPacket $packet, int $targetProtocol): DataPacket;

    public function supportsChunkVersion(int $version): bool;

    public function getChunkVersion(): int;

    public function supportsEncryption(): bool;

    public function supportsCompression(): bool;

    public function getCompressionThreshold(): int;
}
