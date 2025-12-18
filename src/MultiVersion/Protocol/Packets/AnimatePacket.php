<?php
declare(strict_types=1);

namespace MultiVersion\Protocol\Packets;

use MultiVersion\Protocol\Packets\BasePacket;

class AnimatePacket extends BasePacket {
    protected int $packetId = 0x2c;

    public const ACTION_SWING_ARM = 1;
    public const ACTION_STOP_SLEEP = 3;
    public const ACTION_CRITICAL_HIT = 4;
    public const ACTION_MAGIC_CRITICAL_HIT = 5;
    public const ACTION_ROW_RIGHT = 128;
    public const ACTION_ROW_LEFT = 129;

    public int $action;
    public int $entityRuntimeId;
    public float $rowingTime;

    public function encode(): void {
        $stream = $this->getBuffer();
        $this->encodeHeader($stream);
        $stream->putVarInt($this->action);
        $stream->putUnsignedVarLong($this->entityRuntimeId);

        if (($this->action & 0x80) !== 0) {
            $stream->putLFloat($this->rowingTime);
        }

        $this->encoded = true;
    }

    public function decode(): void {
        $stream = $this->getBuffer();
        $this->decodeHeader($stream);
        $this->action = $stream->getVarInt();
        $this->entityRuntimeId = $stream->getUnsignedVarLong();

        if (($this->action & 0x80) !== 0) {
            $this->rowingTime = $stream->getLFloat();
        } else {
            $this->rowingTime = 0.0;
        }

        $this->decoded = true;
    }

    public function handle(object $handler): bool {
        if (method_exists($handler, 'handleAnimate')) {
            return $handler->handleAnimate($this);
        }
        return false;
    }

    public function canBeSentBeforeLogin(): bool {
        return false;
    }

    public function getAction(): int {
        return $this->action;
    }

    public function setAction(int $action): void {
        $validActions = [
            self::ACTION_SWING_ARM,
            self::ACTION_STOP_SLEEP,
            self::ACTION_CRITICAL_HIT,
            self::ACTION_MAGIC_CRITICAL_HIT,
            self::ACTION_ROW_RIGHT,
            self::ACTION_ROW_LEFT
        ];
        if (!in_array($action, $validActions, true)) {
            throw new \InvalidArgumentException("Invalid animate action: {$action}");
        }
        $this->action = $action;
    }

    public function getEntityRuntimeId(): int {
        return $this->entityRuntimeId;
    }

    public function setEntityRuntimeId(int $id): void {
        if ($id < 0) {
            throw new \InvalidArgumentException("Entity runtime ID must be non-negative, got {$id}");
        }
        $this->entityRuntimeId = $id;
    }

    public function getRowingTime(): float {
        return $this->rowingTime;
    }

    public function setRowingTime(float $time): void {
        if ($time < 0.0) {
            throw new \InvalidArgumentException("Rowing time must be non-negative, got {$time}");
        }
        $this->rowingTime = $time;
    }

    public function isRowingAction(): bool {
        return ($this->action & 0x80) !== 0;
    }
}