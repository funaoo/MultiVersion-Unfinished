<?php

declare(strict_types=1);

namespace MultiVersion\Protocol\Packets;

use MultiVersion\Protocol\Packets\BasePacket;

final class RemoveEntityPacket extends BasePacket {

    public int $entityUniqueId;

    public function __construct() {
        $this->packetId = 0x0e;
    }

    public function encode(): void {
        $stream = $this->getBuffer();
        $this->encodeHeader($stream);

        $this->encodeEntityUniqueId($stream, $this->entityUniqueId);

        $this->encoded = true;
    }

    public function decode(): void {
        $stream = $this->getBuffer();
        $this->decodeHeader($stream);

        $this->entityUniqueId = $this->decodeEntityUniqueId($stream);

        $this->decoded = true;
    }

    public function getEntityUniqueId(): int {
        return $this->entityUniqueId;
    }

    public function setEntityUniqueId(int $id): void {
        $this->entityUniqueId = $id;
    }

    public static function create(int $entityUniqueId): self {
        $packet = new self();
        $packet->entityUniqueId = $entityUniqueId;
        return $packet;
    }

    public function handle(object $handler): bool {
        return $handler->handleRemoveEntity($this);
    }
}