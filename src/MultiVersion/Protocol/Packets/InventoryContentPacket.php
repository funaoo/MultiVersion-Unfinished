<?php

declare(strict_types=1);

namespace MultiVersion\Protocol\Packets;

use MultiVersion\Protocol\Packets\BasePacket;
use MultiVersion\Utils\BinaryStream;

final class InventoryContentPacket extends BasePacket {

    public int $windowId;
    public array $items = [];

    public function __construct() {
        $this->packetId = 0x31;
    }

    public function encode(): void {
        $stream = $this->getBuffer();
        $this->encodeHeader($stream);

        $stream->putUnsignedVarInt($this->windowId);
        $stream->putUnsignedVarInt(count($this->items));

        foreach ($this->items as $item) {
            $this->encodeItemStack($stream, $item);
        }

        $this->encoded = true;
    }

    public function decode(): void {
        $stream = $this->getBuffer();
        $this->decodeHeader($stream);

        $this->windowId = $stream->getUnsignedVarInt();
        $count = $stream->getUnsignedVarInt();
        $this->items = [];

        for ($i = 0; $i < $count; $i++) {
            $this->items[] = $this->decodeItemStack($stream);
        }

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

    public function getItems(): array {
        return $this->items;
    }

    public function setItems(array $items): void {
        $this->items = $items;
    }

    public function addItem(object $item): void {
        $this->items[] = $item;
    }

    public function getItem(int $slot): ?object {
        return $this->items[$slot] ?? null;
    }

    public function setItem(int $slot, object $item): void {
        $this->items[$slot] = $item;
    }

    public function removeItem(int $slot): void {
        if (isset($this->items[$slot])) {
            $this->items[$slot] = $this->createEmptyItem();
        }
    }

    public function clear(): void {
        $count = count($this->items);
        $this->items = [];

        for ($i = 0; $i < $count; $i++) {
            $this->items[] = $this->createEmptyItem();
        }
    }

    public function getItemCount(): int {
        return count($this->items);
    }

    public function hasItem(int $slot): bool {
        return isset($this->items[$slot]) && ($this->items[$slot]->id ?? 0) !== 0;
    }

    public function isEmpty(): bool {
        foreach ($this->items as $item) {
            if (($item->id ?? 0) !== 0) {
                return false;
            }
        }
        return true;
    }

    public function isFull(): bool {
        foreach ($this->items as $item) {
            if (($item->id ?? 0) === 0) {
                return false;
            }
        }
        return true;
    }

    public function getEmptySlots(): array {
        $empty = [];
        foreach ($this->items as $slot => $item) {
            if (($item->id ?? 0) === 0) {
                $empty[] = $slot;
            }
        }
        return $empty;
    }

    public function getFilledSlots(): array {
        $filled = [];
        foreach ($this->items as $slot => $item) {
            if (($item->id ?? 0) !== 0) {
                $filled[] = $slot;
            }
        }
        return $filled;
    }

    public function countEmptySlots(): int {
        return count($this->getEmptySlots());
    }

    public function countFilledSlots(): int {
        return count($this->getFilledSlots());
    }

    private function createEmptyItem(): object {
        $item = new \stdClass();
        $item->id = 0;
        return $item;
    }

    public static function create(int $windowId, array $items): self {
        $packet = new self();
        $packet->windowId = $windowId;
        $packet->items = $items;
        return $packet;
    }

    public static function createEmpty(int $windowId, int $slotCount): self {
        $packet = new self();
        $packet->windowId = $windowId;
        $packet->items = [];

        for ($i = 0; $i < $slotCount; $i++) {
            $item = new \stdClass();
            $item->id = 0;
            $packet->items[] = $item;
        }

        return $packet;
    }

    public function handle(object $handler): bool {
        return $handler->handleInventoryContent($this);
    }
}