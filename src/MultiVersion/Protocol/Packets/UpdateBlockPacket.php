<?php

declare(strict_types=1);

namespace MultiVersion\Protocol\Packets;

use MultiVersion\Protocol\Packets\BasePacket;

final class UpdateBlockPacket extends BasePacket {

    public const FLAG_NONE = 0b0000;
    public const FLAG_NEIGHBORS = 0b0001;
    public const FLAG_NETWORK = 0b0010;
    public const FLAG_NO_GRAPHIC = 0b0100;
    public const FLAG_PRIORITY = 0b1000;
    public const FLAG_ALL = self::FLAG_NEIGHBORS | self::FLAG_NETWORK;
    public const FLAG_ALL_PRIORITY = self::FLAG_ALL | self::FLAG_PRIORITY;

    public const DATA_LAYER_NORMAL = 0;
    public const DATA_LAYER_LIQUID = 1;

    public object $blockPosition;
    public int $blockRuntimeId;
    public int $flags;
    public int $dataLayerId;

    public function __construct() {
        $this->packetId = 0x15;
    }

    public function encode(): void {
        $stream = $this->getBuffer();
        $this->encodeHeader($stream);

        $this->encodeBlockPosition($stream, $this->blockPosition);
        $stream->putUnsignedVarInt($this->blockRuntimeId);
        $stream->putUnsignedVarInt($this->flags);
        $stream->putUnsignedVarInt($this->dataLayerId);

        $this->encoded = true;
    }

    public function decode(): void {
        $stream = $this->getBuffer();
        $this->decodeHeader($stream);

        $this->blockPosition = $this->decodeBlockPosition($stream);
        $this->blockRuntimeId = $stream->getUnsignedVarInt();
        $this->flags = $stream->getUnsignedVarInt();
        $this->dataLayerId = $stream->getUnsignedVarInt();

        $this->decoded = true;
    }

    public function getBlockPosition(): object {
        return $this->blockPosition;
    }

    public function getBlockRuntimeId(): int {
        return $this->blockRuntimeId;
    }

    public function getFlags(): int {
        return $this->flags;
    }

    public function getDataLayerId(): int {
        return $this->dataLayerId;
    }

    public function hasFlag(int $flag): bool {
        return ($this->flags & $flag) !== 0;
    }

    public function updateNeighbors(): bool {
        return $this->hasFlag(self::FLAG_NEIGHBORS);
    }

    public function sendToNetwork(): bool {
        return $this->hasFlag(self::FLAG_NETWORK);
    }

    public function isPriority(): bool {
        return $this->hasFlag(self::FLAG_PRIORITY);
    }

    public function isLiquidLayer(): bool {
        return $this->dataLayerId === self::DATA_LAYER_LIQUID;
    }

    public function isNormalLayer(): bool {
        return $this->dataLayerId === self::DATA_LAYER_NORMAL;
    }

    public static function create(object $position, int $blockRuntimeId, int $flags = self::FLAG_ALL, int $dataLayer = self::DATA_LAYER_NORMAL): self {
        $packet = new self();
        $packet->blockPosition = $position;
        $packet->blockRuntimeId = $blockRuntimeId;
        $packet->flags = $flags;
        $packet->dataLayerId = $dataLayer;
        return $packet;
    }

    public function handle(object $handler): bool {
        return $handler->handleUpdateBlock($this);
    }
}