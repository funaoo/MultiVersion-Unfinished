<?php

declare(strict_types=1);

namespace MultiVersion\Protocol\Packets;

use MultiVersion\Protocol\Packets\BasePacket;
use MultiVersion\Utils\BinaryStream;

final class MobEquipmentPacket extends BasePacket {

    public int $entityRuntimeId;
    public object $item;
    public int $inventorySlot;
    public int $hotbarSlot;
    public int $windowId;

    public function __construct() {
        $this->packetId = 0x1f;
    }

    public function encode(): void {
        $stream = $this->getBuffer();
        $this->encodeHeader($stream);

        $this->encodeEntityRuntimeId($stream, $this->entityRuntimeId);
        $this->encodeItemStack($stream, $this->item);
        $stream->putByte($this->inventorySlot);
        $stream->putByte($this->hotbarSlot);
        $stream->putByte($this->windowId);

        $this->encoded = true;
    }

    public function decode(): void {
        $stream = $this->getBuffer();
        $this->decodeHeader($stream);

        $this->entityRuntimeId = $this->decodeEntityRuntimeId($stream);
        $this->item = $this->decodeItemStack($stream);
        $this->inventorySlot = $stream->getByte();
        $this->hotbarSlot = $stream->getByte();
        $this->windowId = $stream->getByte();

        $this->decoded = true;
    }

    private function encodeItemStack(BinaryStream $stream, object $item): void {
        $stream->putVarInt($item->id ?? 0);

        if (($item->id ?? 0) === 0) {
            return;
        }

        $stream->putVarInt($item->count ?? 1);
        $stream->putUnsignedVarInt($item->meta ?? 0);
        $stream->putBool(isset($item->nbt) && $item->nbt !== '');

        if (isset($item->nbt) && $item->nbt !== '') {
            $stream->putLShort(strlen($item->nbt));
            $stream->put($item->nbt);
        }

        $stream->putVarInt(count($item->canPlaceOn ?? []));
        foreach ($item->canPlaceOn ?? [] as $block) {
            $stream->putString($block);
        }

        $stream->putVarInt(count($item->canDestroy ?? []));
        foreach ($item->canDestroy ?? [] as $block) {
            $stream->putString($block);
        }

        if (($item->id ?? 0) === 513) {
            $stream->putLLong($item->shieldBlockingTick ?? 0);
        }
    }

    private function decodeItemStack(BinaryStream $stream): object {
        $item = new \stdClass();
        $item->id = $stream->getVarInt();

        if ($item->id === 0) {
            return $item;
        }

        $item->count = $stream->getVarInt();
        $item->meta = $stream->getUnsignedVarInt();
        $hasNbt = $stream->getBool();

        if ($hasNbt) {
            $nbtLength = $stream->getLShort();
            $item->nbt = $stream->get($nbtLength);
        }

        $canPlaceOnCount = $stream->getVarInt();
        $item->canPlaceOn = [];
        for ($i = 0; $i < $canPlaceOnCount; $i++) {
            $item->canPlaceOn[] = $stream->getString();
        }

        $canDestroyCount = $stream->getVarInt();
        $item->canDestroy = [];
        for ($i = 0; $i < $canDestroyCount; $i++) {
            $item->canDestroy[] = $stream->getString();
        }

        if ($item->id === 513) {
            $item->shieldBlockingTick = $stream->getLLong();
        }

        return $item;
    }

    public function getEntityRuntimeId(): int {
        return $this->entityRuntimeId;
    }

    public function setEntityRuntimeId(int $id): void {
        $this->entityRuntimeId = $id;
    }

    public function getItem(): object {
        return $this->item;
    }

    public function setItem(object $item): void {
        $this->item = $item;
    }

    public function getInventorySlot(): int {
        return $this->inventorySlot;
    }

    public function setInventorySlot(int $slot): void {
        $this->inventorySlot = $slot;
    }

    public function getHotbarSlot(): int {
        return $this->hotbarSlot;
    }

    public function setHotbarSlot(int $slot): void {
        $this->hotbarSlot = $slot;
    }

    public function getWindowId(): int {
        return $this->windowId;
    }

    public function setWindowId(int $id): void {
        $this->windowId = $id;
    }

    public function isEmpty(): bool {
        return ($this->item->id ?? 0) === 0;
    }

    public function getItemId(): int {
        return $this->item->id ?? 0;
    }

    public function getItemCount(): int {
        return $this->item->count ?? 0;
    }

    public function getItemMeta(): int {
        return $this->item->meta ?? 0;
    }

    public function hasNBT(): bool {
        return isset($this->item->nbt) && $this->item->nbt !== '';
    }

    public static function create(int $entityRuntimeId, object $item, int $inventorySlot, int $hotbarSlot, int $windowId = 0): self {
        $packet = new self();
        $packet->entityRuntimeId = $entityRuntimeId;
        $packet->item = $item;
        $packet->inventorySlot = $inventorySlot;
        $packet->hotbarSlot = $hotbarSlot;
        $packet->windowId = $windowId;
        return $packet;
    }

    public static function createEmpty(int $entityRuntimeId, int $inventorySlot, int $hotbarSlot): self {
        $packet = new self();
        $packet->entityRuntimeId = $entityRuntimeId;
        $packet->item = new \stdClass();
        $packet->item->id = 0;
        $packet->inventorySlot = $inventorySlot;
        $packet->hotbarSlot = $hotbarSlot;
        $packet->windowId = 0;
        return $packet;
    }

    public function handle(object $handler): bool {
        return $handler->handleMobEquipment($this);
    }
}