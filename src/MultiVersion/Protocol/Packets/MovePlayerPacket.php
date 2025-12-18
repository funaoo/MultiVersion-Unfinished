<?php

declare(strict_types=1);

namespace MultiVersion\Protocol\Packets;

use MultiVersion\Protocol\Packets\BasePacket;

final class MovePlayerPacket extends BasePacket {

    public const MODE_NORMAL = 0;
    public const MODE_RESET = 1;
    public const MODE_TELEPORT = 2;
    public const MODE_PITCH = 3;

    public int $entityRuntimeId;
    public object $position;
    public float $pitch;
    public float $yaw;
    public float $headYaw;
    public int $mode;
    public bool $onGround;
    public int $ridingEntityRuntimeId;
    public int $teleportCause;
    public int $teleportItem;
    public int $tick;

    public function __construct() {
        $this->packetId = 0x13;
    }

    public function encode(): void {
        $stream = $this->getBuffer();
        $this->encodeHeader($stream);

        $this->encodeEntityRuntimeId($stream, $this->entityRuntimeId);
        $this->encodeVector3($stream, $this->position);
        $stream->putLFloat($this->pitch);
        $stream->putLFloat($this->yaw);
        $stream->putLFloat($this->headYaw);
        $stream->putByte($this->mode);
        $stream->putBool($this->onGround);
        $this->encodeEntityRuntimeId($stream, $this->ridingEntityRuntimeId);

        if ($this->mode === self::MODE_TELEPORT) {
            $stream->putLInt($this->teleportCause);
            $stream->putLInt($this->teleportItem);
        }

        $stream->putUnsignedVarLong($this->tick);

        $this->encoded = true;
    }

    public function decode(): void {
        $stream = $this->getBuffer();
        $this->decodeHeader($stream);

        $this->entityRuntimeId = $this->decodeEntityRuntimeId($stream);
        $this->position = $this->decodeVector3($stream);
        $this->pitch = $stream->getLFloat();
        $this->yaw = $stream->getLFloat();
        $this->headYaw = $stream->getLFloat();
        $this->mode = $stream->getByte();
        $this->onGround = $stream->getBool();
        $this->ridingEntityRuntimeId = $this->decodeEntityRuntimeId($stream);

        if ($this->mode === self::MODE_TELEPORT) {
            $this->teleportCause = $stream->getLInt();
            $this->teleportItem = $stream->getLInt();
        }

        $this->tick = $stream->getUnsignedVarLong();

        $this->decoded = true;
    }

    public function isNormalMove(): bool {
        return $this->mode === self::MODE_NORMAL;
    }

    public function isReset(): bool {
        return $this->mode === self::MODE_RESET;
    }

    public function isTeleport(): bool {
        return $this->mode === self::MODE_TELEPORT;
    }

    public function isPitchOnly(): bool {
        return $this->mode === self::MODE_PITCH;
    }

    public function isRiding(): bool {
        return $this->ridingEntityRuntimeId !== 0;
    }

    public static function create(int $entityId, object $position, float $pitch, float $yaw, float $headYaw, int $mode = self::MODE_NORMAL, bool $onGround = false, int $tick = 0): self {
        $packet = new self();
        $packet->entityRuntimeId = $entityId;
        $packet->position = $position;
        $packet->pitch = $pitch;
        $packet->yaw = $yaw;
        $packet->headYaw = $headYaw;
        $packet->mode = $mode;
        $packet->onGround = $onGround;
        $packet->ridingEntityRuntimeId = 0;
        $packet->teleportCause = 0;
        $packet->teleportItem = 0;
        $packet->tick = $tick;
        return $packet;
    }

    public static function teleport(int $entityId, object $position, float $pitch, float $yaw, float $headYaw, int $cause = 0, int $item = 0): self {
        $packet = self::create($entityId, $position, $pitch, $yaw, $headYaw, self::MODE_TELEPORT);
        $packet->teleportCause = $cause;
        $packet->teleportItem = $item;
        return $packet;
    }

    public function handle(object $handler): bool {
        return $handler->handleMovePlayer($this);
    }
}