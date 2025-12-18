<?php

declare(strict_types=1);

namespace MultiVersion\Protocol\Packets;

use MultiVersion\Protocol\Packets\BasePacket;

final class ContainerOpenPacket extends BasePacket {

    public const TYPE_CONTAINER = -1;
    public const TYPE_INVENTORY = 0;
    public const TYPE_CHEST = 1;
    public const TYPE_CRAFTING_TABLE = 2;
    public const TYPE_FURNACE = 3;
    public const TYPE_ENCHANTMENT_TABLE = 4;
    public const TYPE_BREWING_STAND = 5;
    public const TYPE_ANVIL = 6;
    public const TYPE_DISPENSER = 7;
    public const TYPE_DROPPER = 8;
    public const TYPE_HOPPER = 9;
    public const TYPE_CAULDRON = 10;
    public const TYPE_MINECART_CHEST = 11;
    public const TYPE_MINECART_HOPPER = 12;
    public const TYPE_HORSE = 13;
    public const TYPE_BEACON = 14;
    public const TYPE_STRUCTURE_EDITOR = 15;
    public const TYPE_TRADING = 16;
    public const TYPE_COMMAND_BLOCK = 17;
    public const TYPE_JUKEBOX = 18;
    public const TYPE_ARMOR = 19;
    public const TYPE_HAND = 20;
    public const TYPE_COMPOUND_CREATOR = 21;
    public const TYPE_ELEMENT_CONSTRUCTOR = 22;
    public const TYPE_MATERIAL_REDUCER = 23;
    public const TYPE_LAB_TABLE = 24;
    public const TYPE_LOOM = 25;
    public const TYPE_LECTERN = 26;
    public const TYPE_GRINDSTONE = 27;
    public const TYPE_BLAST_FURNACE = 28;
    public const TYPE_SMOKER = 29;
    public const TYPE_STONECUTTER = 30;
    public const TYPE_CARTOGRAPHY_TABLE = 31;
    public const TYPE_HUD = 32;
    public const TYPE_SMITHING_TABLE = 33;

    public int $windowId;
    public int $windowType;
    public object $position;
    public int $entityUniqueId;

    public function __construct() {
        $this->packetId = 0x2e;
    }

    public function encode(): void {
        $stream = $this->getBuffer();
        $this->encodeHeader($stream);

        $stream->putByte($this->windowId);
        $stream->putByte($this->windowType);
        $this->encodeBlockPosition($stream, $this->position);
        $this->encodeEntityUniqueId($stream, $this->entityUniqueId);

        $this->encoded = true;
    }

    public function decode(): void {
        $stream = $this->getBuffer();
        $this->decodeHeader($stream);

        $this->windowId = $stream->getByte();
        $this->windowType = $stream->getByte();
        $this->position = $this->decodeBlockPosition($stream);
        $this->entityUniqueId = $this->decodeEntityUniqueId($stream);

        $this->decoded = true;
    }

    public function getWindowId(): int {
        return $this->windowId;
    }

    public function setWindowId(int $id): void {
        $this->windowId = $id;
    }

    public function getWindowType(): int {
        return $this->windowType;
    }

    public function setWindowType(int $type): void {
        $this->windowType = $type;
    }

    public function getPosition(): object {
        return $this->position;
    }

    public function setPosition(object $position): void {
        $this->position = $position;
    }

    public function getEntityUniqueId(): int {
        return $this->entityUniqueId;
    }

    public function setEntityUniqueId(int $id): void {
        $this->entityUniqueId = $id;
    }

    public function isChest(): bool {
        return $this->windowType === self::TYPE_CHEST;
    }

    public function isEnderChest(): bool {
        return $this->windowType === self::TYPE_CHEST && $this->entityUniqueId === -1;
    }

    public function isFurnace(): bool {
        return in_array($this->windowType, [
            self::TYPE_FURNACE,
            self::TYPE_BLAST_FURNACE,
            self::TYPE_SMOKER
        ], true);
    }

    public function isCraftingTable(): bool {
        return $this->windowType === self::TYPE_CRAFTING_TABLE;
    }

    public function isEnchantmentTable(): bool {
        return $this->windowType === self::TYPE_ENCHANTMENT_TABLE;
    }

    public function isAnvil(): bool {
        return $this->windowType === self::TYPE_ANVIL;
    }

    public function isBeacon(): bool {
        return $this->windowType === self::TYPE_BEACON;
    }

    public function isTrading(): bool {
        return $this->windowType === self::TYPE_TRADING;
    }

    public function isHorse(): bool {
        return $this->windowType === self::TYPE_HORSE;
    }

    public function isBrewingStand(): bool {
        return $this->windowType === self::TYPE_BREWING_STAND;
    }

    public function isHopper(): bool {
        return $this->windowType === self::TYPE_HOPPER;
    }

    public function isDropper(): bool {
        return $this->windowType === self::TYPE_DROPPER;
    }

    public function isDispenser(): bool {
        return $this->windowType === self::TYPE_DISPENSER;
    }

    public function isLoom(): bool {
        return $this->windowType === self::TYPE_LOOM;
    }

    public function isSmithingTable(): bool {
        return $this->windowType === self::TYPE_SMITHING_TABLE;
    }

    public function isGrindstone(): bool {
        return $this->windowType === self::TYPE_GRINDSTONE;
    }

    public function isStonecutter(): bool {
        return $this->windowType === self::TYPE_STONECUTTER;
    }

    public function isCartographyTable(): bool {
        return $this->windowType === self::TYPE_CARTOGRAPHY_TABLE;
    }

    public function getWindowTypeName(): string {
        return match($this->windowType) {
            self::TYPE_CONTAINER => 'CONTAINER',
            self::TYPE_INVENTORY => 'INVENTORY',
            self::TYPE_CHEST => 'CHEST',
            self::TYPE_CRAFTING_TABLE => 'CRAFTING_TABLE',
            self::TYPE_FURNACE => 'FURNACE',
            self::TYPE_ENCHANTMENT_TABLE => 'ENCHANTMENT_TABLE',
            self::TYPE_BREWING_STAND => 'BREWING_STAND',
            self::TYPE_ANVIL => 'ANVIL',
            self::TYPE_DISPENSER => 'DISPENSER',
            self::TYPE_DROPPER => 'DROPPER',
            self::TYPE_HOPPER => 'HOPPER',
            self::TYPE_CAULDRON => 'CAULDRON',
            self::TYPE_MINECART_CHEST => 'MINECART_CHEST',
            self::TYPE_MINECART_HOPPER => 'MINECART_HOPPER',
            self::TYPE_HORSE => 'HORSE',
            self::TYPE_BEACON => 'BEACON',
            self::TYPE_STRUCTURE_EDITOR => 'STRUCTURE_EDITOR',
            self::TYPE_TRADING => 'TRADING',
            self::TYPE_COMMAND_BLOCK => 'COMMAND_BLOCK',
            self::TYPE_JUKEBOX => 'JUKEBOX',
            self::TYPE_ARMOR => 'ARMOR',
            self::TYPE_HAND => 'HAND',
            self::TYPE_COMPOUND_CREATOR => 'COMPOUND_CREATOR',
            self::TYPE_ELEMENT_CONSTRUCTOR => 'ELEMENT_CONSTRUCTOR',
            self::TYPE_MATERIAL_REDUCER => 'MATERIAL_REDUCER',
            self::TYPE_LAB_TABLE => 'LAB_TABLE',
            self::TYPE_LOOM => 'LOOM',
            self::TYPE_LECTERN => 'LECTERN',
            self::TYPE_GRINDSTONE => 'GRINDSTONE',
            self::TYPE_BLAST_FURNACE => 'BLAST_FURNACE',
            self::TYPE_SMOKER => 'SMOKER',
            self::TYPE_STONECUTTER => 'STONECUTTER',
            self::TYPE_CARTOGRAPHY_TABLE => 'CARTOGRAPHY_TABLE',
            self::TYPE_HUD => 'HUD',
            self::TYPE_SMITHING_TABLE => 'SMITHING_TABLE',
            default => 'UNKNOWN'
        };
    }

    public static function create(int $windowId, int $windowType, object $position, int $entityUniqueId = -1): self {
        $packet = new self();
        $packet->windowId = $windowId;
        $packet->windowType = $windowType;
        $packet->position = $position;
        $packet->entityUniqueId = $entityUniqueId;
        return $packet;
    }

    public function handle(object $handler): bool {
        return $handler->handleContainerOpen($this);
    }
}