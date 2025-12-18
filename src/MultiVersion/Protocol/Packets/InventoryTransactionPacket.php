<?php

declare(strict_types=1);

namespace MultiVersion\Protocol\Packets;

use MultiVersion\Protocol\Packets\BasePacket;
use MultiVersion\Utils\BinaryStream;

final class InventoryTransactionPacket extends BasePacket {

    public const TYPE_NORMAL = 0;
    public const TYPE_MISMATCH = 1;
    public const TYPE_USE_ITEM = 2;
    public const TYPE_USE_ITEM_ON_ENTITY = 3;
    public const TYPE_RELEASE_ITEM = 4;

    public const USE_ITEM_ACTION_CLICK_BLOCK = 0;
    public const USE_ITEM_ACTION_CLICK_AIR = 1;
    public const USE_ITEM_ACTION_BREAK_BLOCK = 2;

    public const RELEASE_ITEM_ACTION_RELEASE = 0;
    public const RELEASE_ITEM_ACTION_CONSUME = 1;

    public const USE_ITEM_ON_ENTITY_ACTION_INTERACT = 0;
    public const USE_ITEM_ON_ENTITY_ACTION_ATTACK = 1;

    public int $transactionType;
    public array $actions = [];
    public int $actionType;
    public object $blockPosition;
    public int $face;
    public int $hotbarSlot;
    public object $itemInHand;
    public object $playerPosition;
    public object $clickPosition;
    public int $blockRuntimeId;
    public int $entityRuntimeId;

    public function __construct() {
        $this->packetId = 0x1e;
    }

    public function encode(): void {
        $stream = $this->getBuffer();
        $this->encodeHeader($stream);

        $stream->putUnsignedVarInt($this->transactionType);

        $stream->putUnsignedVarInt(count($this->actions));
        foreach ($this->actions as $action) {
            $this->encodeAction($stream, $action);
        }

        match($this->transactionType) {
            self::TYPE_NORMAL, self::TYPE_MISMATCH => null,
            self::TYPE_USE_ITEM => $this->encodeUseItem($stream),
            self::TYPE_USE_ITEM_ON_ENTITY => $this->encodeUseItemOnEntity($stream),
            self::TYPE_RELEASE_ITEM => $this->encodeReleaseItem($stream),
            default => null
        };

        $this->encoded = true;
    }

    public function decode(): void {
        $stream = $this->getBuffer();
        $this->decodeHeader($stream);

        $this->transactionType = $stream->getUnsignedVarInt();

        $actionCount = $stream->getUnsignedVarInt();
        $this->actions = [];
        for ($i = 0; $i < $actionCount; $i++) {
            $this->actions[] = $this->decodeAction($stream);
        }

        match($this->transactionType) {
            self::TYPE_NORMAL, self::TYPE_MISMATCH => null,
            self::TYPE_USE_ITEM => $this->decodeUseItem($stream),
            self::TYPE_USE_ITEM_ON_ENTITY => $this->decodeUseItemOnEntity($stream),
            self::TYPE_RELEASE_ITEM => $this->decodeReleaseItem($stream),
            default => null
        };

        $this->decoded = true;
    }

    private function encodeAction(BinaryStream $stream, array $action): void {
        $stream->putUnsignedVarInt($action['sourceType']);

        match($action['sourceType']) {
            0 => $stream->putVarInt($action['windowId']),
            2 => $stream->putUnsignedVarInt($action['sourceFlags']),
            3 => $stream->putUnsignedVarInt($action['windowId']),
            default => null
        };

        $stream->putUnsignedVarInt($action['slot']);
        $this->encodeItem($stream, $action['oldItem']);
        $this->encodeItem($stream, $action['newItem']);
    }

    private function decodeAction(BinaryStream $stream): array {
        $action = [];
        $action['sourceType'] = $stream->getUnsignedVarInt();

        match($action['sourceType']) {
            0 => $action['windowId'] = $stream->getVarInt(),
            2 => $action['sourceFlags'] = $stream->getUnsignedVarInt(),
            3 => $action['windowId'] = $stream->getUnsignedVarInt(),
            default => null
        };

        $action['slot'] = $stream->getUnsignedVarInt();
        $action['oldItem'] = $this->decodeItem($stream);
        $action['newItem'] = $this->decodeItem($stream);

        return $action;
    }

    private function encodeUseItem(BinaryStream $stream): void {
        $stream->putUnsignedVarInt($this->actionType);
        $this->encodeBlockPosition($stream, $this->blockPosition);
        $stream->putVarInt($this->face);
        $stream->putVarInt($this->hotbarSlot);
        $this->encodeItem($stream, $this->itemInHand);
        $this->encodeVector3($stream, $this->playerPosition);
        $this->encodeVector3($stream, $this->clickPosition);
        $stream->putUnsignedVarInt($this->blockRuntimeId);
    }

    private function decodeUseItem(BinaryStream $stream): void {
        $this->actionType = $stream->getUnsignedVarInt();
        $this->blockPosition = $this->decodeBlockPosition($stream);
        $this->face = $stream->getVarInt();
        $this->hotbarSlot = $stream->getVarInt();
        $this->itemInHand = $this->decodeItem($stream);
        $this->playerPosition = $this->decodeVector3($stream);
        $this->clickPosition = $this->decodeVector3($stream);
        $this->blockRuntimeId = $stream->getUnsignedVarInt();
    }

    private function encodeUseItemOnEntity(BinaryStream $stream): void {
        $this->encodeEntityRuntimeId($stream, $this->entityRuntimeId);
        $stream->putUnsignedVarInt($this->actionType);
        $stream->putVarInt($this->hotbarSlot);
        $this->encodeItem($stream, $this->itemInHand);
        $this->encodeVector3($stream, $this->playerPosition);
        $this->encodeVector3($stream, $this->clickPosition);
    }

    private function decodeUseItemOnEntity(BinaryStream $stream): void {
        $this->entityRuntimeId = $this->decodeEntityRuntimeId($stream);
        $this->actionType = $stream->getUnsignedVarInt();
        $this->hotbarSlot = $stream->getVarInt();
        $this->itemInHand = $this->decodeItem($stream);
        $this->playerPosition = $this->decodeVector3($stream);
        $this->clickPosition = $this->decodeVector3($stream);
    }

    private function encodeReleaseItem(BinaryStream $stream): void {
        $stream->putUnsignedVarInt($this->actionType);
        $stream->putVarInt($this->hotbarSlot);
        $this->encodeItem($stream, $this->itemInHand);
        $this->encodeVector3($stream, $this->playerPosition);
    }

    private function decodeReleaseItem(BinaryStream $stream): void {
        $this->actionType = $stream->getUnsignedVarInt();
        $this->hotbarSlot = $stream->getVarInt();
        $this->itemInHand = $this->decodeItem($stream);
        $this->playerPosition = $this->decodeVector3($stream);
    }

    private function encodeItem(BinaryStream $stream, object $item): void {
        $stream->putVarInt($item->id ?? 0);

        if (($item->id ?? 0) === 0) {
            return;
        }

        $stream->putVarInt($item->count ?? 1);
        $stream->putUnsignedVarInt($item->meta ?? 0);
        $stream->putBool(isset($item->nbt));

        if (isset($item->nbt)) {
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
    }

    private function decodeItem(BinaryStream $stream): object {
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

        return $item;
    }

    public function isNormalTransaction(): bool {
        return $this->transactionType === self::TYPE_NORMAL;
    }

    public function isUseItem(): bool {
        return $this->transactionType === self::TYPE_USE_ITEM;
    }

    public function isUseItemOnEntity(): bool {
        return $this->transactionType === self::TYPE_USE_ITEM_ON_ENTITY;
    }

    public function isReleaseItem(): bool {
        return $this->transactionType === self::TYPE_RELEASE_ITEM;
    }

    public function handle(object $handler): bool {
        return $handler->handleInventoryTransaction($this);
    }
}