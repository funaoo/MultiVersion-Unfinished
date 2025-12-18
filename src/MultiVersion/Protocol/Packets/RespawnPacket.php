<?php

declare(strict_types=1);

namespace MultiVersion\Protocol\Packets;

use MultiVersion\Protocol\Packets\BasePacket;

final class RespawnPacket extends BasePacket {

    public const STATE_SEARCHING_FOR_SPAWN = 0;
    public const STATE_READY_TO_SPAWN = 1;
    public const STATE_CLIENT_READY_TO_SPAWN = 2;

    public object $position;
    public int $respawnState;
    public int $entityRuntimeId;

    public function __construct() {
        $this->packetId = 0x2d;
    }

    public function encode(): void {
        $stream = $this->getBuffer();
        $this->encodeHeader($stream);

        $this->encodeVector3($stream, $this->position);
        $stream->putByte($this->respawnState);
        $this->encodeEntityRuntimeId($stream, $this->entityRuntimeId);

        $this->encoded = true;
    }

    public function decode(): void {
        $stream = $this->getBuffer();
        $this->decodeHeader($stream);

        $this->position = $this->decodeVector3($stream);
        $this->respawnState = $stream->getByte();
        $this->entityRuntimeId = $this->decodeEntityRuntimeId($stream);

        $this->decoded = true;
    }

    public function getPosition(): object {
        return $this->position;
    }

    public function setPosition(object $position): void {
        $this->position = $position;
    }

    public function getRespawnState(): int {
        return $this->respawnState;
    }

    public function setRespawnState(int $state): void {
        $this->respawnState = $state;
    }

    public function getEntityRuntimeId(): int {
        return $this->entityRuntimeId;
    }

    public function setEntityRuntimeId(int $id): void {
        $this->entityRuntimeId = $id;
    }

    public function isSearchingForSpawn(): bool {
        return $this->respawnState === self::STATE_SEARCHING_FOR_SPAWN;
    }

    public function isReadyToSpawn(): bool {
        return $this->respawnState === self::STATE_READY_TO_SPAWN;
    }

    public function isClientReadyToSpawn(): bool {
        return $this->respawnState === self::STATE_CLIENT_READY_TO_SPAWN;
    }

    public function getStateName(): string {
        return match($this->respawnState) {
            self::STATE_SEARCHING_FOR_SPAWN => 'SEARCHING_FOR_SPAWN',
            self::STATE_READY_TO_SPAWN => 'READY_TO_SPAWN',
            self::STATE_CLIENT_READY_TO_SPAWN => 'CLIENT_READY_TO_SPAWN',
            default => 'UNKNOWN'
        };
    }

    public static function create(object $position, int $state, int $entityRuntimeId): self {
        $packet = new self();
        $packet->position = $position;
        $packet->respawnState = $state;
        $packet->entityRuntimeId = $entityRuntimeId;
        return $packet;
    }

    public static function searchingForSpawn(object $position, int $entityRuntimeId): self {
        return self::create($position, self::STATE_SEARCHING_FOR_SPAWN, $entityRuntimeId);
    }

    public static function readyToSpawn(object $position, int $entityRuntimeId): self {
        return self::create($position, self::STATE_READY_TO_SPAWN, $entityRuntimeId);
    }

    public static function clientReady(object $position, int $entityRuntimeId): self {
        return self::create($position, self::STATE_CLIENT_READY_TO_SPAWN, $entityRuntimeId);
    }

    public function handle(object $handler): bool {
        return $handler->handleRespawn($this);
    }
}