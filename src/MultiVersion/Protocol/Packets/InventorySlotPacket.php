<?php

declare(strict_types=1);

namespace MultiVersion\Protocol\Packets;

use MultiVersion\Protocol\Packets\BasePacket;
use MultiVersion\Utils\BinaryStream;

final class InventorySlotPacket extends BasePacket {

    public int $windowId;
    public int $slot;
    public object $item;

    public function __construct() {
        $this->packetId = 0x32;
    }

    public function encode(): void {
        $stream = $this->getBuffer();
        $this->encodeHeader($stream);

        $stream->putUnsignedVarInt($this->windowId);
        $stream->putUnsignedVarInt($this->slot);
        $this->encodeItemStack($stream, $this->item);

        $this->encoded = true;
    }

    public function decode(): void {
        $stream = $this->getBuffer();
        $this->decodeHeader($stream);

        $this->windowId = $stream->getUnsignedVarInt();
        $this->slot = $stream->getUnsignedVarInt();
        $this->item = $this->decodeItemStack($stream);

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

    public function getWindowId(): int {
        return $this->windowId;
    }

    public function setWindowId(int $id): void {
        $this->windowId = $id;
    }

    public function getSlot(): int {
        return $this->slot;
    }

    public function setSlot(int $slot): void {
        $this->slot = $slot;
    }

    public function getItem(): object {
        return $this->item;
    }

    public function setItem(object $item): void {
        $this->item = $item;
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

    public function getNBT(): string {
        return $this->item->nbt ?? '';
    }

    public function setNBT(string $nbt): void {
        if ($nbt === '') {
            unset($this->item->nbt);
        } else {
            $this->item->nbt = $nbt;
        }
    }

    public function getCanPlaceOn(): array {
        return $this->item->canPlaceOn ?? [];
    }

    public function setCanPlaceOn(array $blocks): void {
        $this->item->canPlaceOn = $blocks;
    }

    public function getCanDestroy(): array {
        return $this->item->canDestroy ?? [];
    }

    public function setCanDestroy(array $blocks): void {
        $this->item->canDestroy = $blocks;
    }

    public function isShield(): bool {
        return ($this->item->id ?? 0) === 513;
    }

    public function getShieldBlockingTick(): int {
        return $this->item->shieldBlockingTick ?? 0;
    }

    public static function create(int $windowId, int $slot, object $item): self {
        $packet = new self();
        $packet->windowId = $windowId;
        $packet->slot = $slot;
        $packet->item = $item;
        return $packet;
    }

    public static function createEmpty(int $windowId, int $slot): self {
        $packet = new self();
        $packet->windowId = $windowId;
        $packet->slot = $slot;
        $packet->item = new \stdClass();
        $packet->item->id = 0;
        return $packet;
    }

    public static function createWithItem(int $windowId, int $slot, int $itemId, int $count = 1, int $meta = 0): self {
        $packet = new self();
        $packet->windowId = $windowId;
        $packet->slot = $slot;
        $packet->item = new \stdClass();
        $packet->item->id = $itemId;
        $packet->item->count = $count;
        $packet->item->meta = $meta;
        return $packet;
    }

    public function handle(object $handler): bool {
        return $handler->handleInventorySlot($this);
    }
}