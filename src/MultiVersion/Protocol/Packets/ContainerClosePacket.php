<?php

declare(strict_types=1);

namespace MultiVersion\Protocol\Packets;

use MultiVersion\Protocol\Packets\BasePacket;

final class ContainerClosePacket extends BasePacket {

    public int $windowId;
    public bool $serverInitiated;

    public function __construct() {
        $this->packetId = 0x2f;
    }

    public function encode(): void {
        $stream = $this->getBuffer();
        $this->encodeHeader($stream);

        $stream->putByte($this->windowId);
        $stream->putBool($this->serverInitiated);

        $this->encoded = true;
    }

    public function decode(): void {
        $stream = $this->getBuffer();
        $this->decodeHeader($stream);

        $this->windowId = $stream->getByte();
        $this->serverInitiated = $stream->getBool();

        $this->decoded = true;
    }

    public function getWindowId(): int {
        return $this->windowId;
    }

    public function setWindowId(int $id): void {
        $this->windowId = $id;
    }

    public function isServerInitiated(): bool {
        return $this->serverInitiated;
    }

    public function setServerInitiated(bool $serverInitiated): void {
        $this->serverInitiated = $serverInitiated;
    }

    public function isClientInitiated(): bool {
        return !$this->serverInitiated;
    }

    public static function create(int $windowId, bool $serverInitiated = false): self {
        $packet = new self();
        $packet->windowId = $windowId;
        $packet->serverInitiated = $serverInitiated;
        return $packet;
    }

    public static function fromServer(int $windowId): self {
        return self::create($windowId, true);
    }

    public static function fromClient(int $windowId): self {
        return self::create($windowId, false);
    }

    public function handle(object $handler): bool {
        return $handler->handleContainerClose($this);
    }
}