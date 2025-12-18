<?php
declare(strict_types=1);

namespace MultiVersion\Protocol\Packets;

use MultiVersion\Protocol\Packets\BasePacket;

class SetLocalPlayerAsInitializedPacket extends BasePacket {
    protected int $packetId = 0x47;
    public int $entityRuntimeId;

    public function encode(): void {
        $stream = $this->getBuffer();
        $this->encodeHeader($stream);
        $stream->putUnsignedVarLong($this->entityRuntimeId);
        $this->encoded = true;
    }

    public function decode(): void {
        $stream = $this->getBuffer();
        $this->decodeHeader($stream);
        $this->entityRuntimeId = $stream->getUnsignedVarLong();
        $this->decoded = true;
    }

    public function handle(object $handler): bool {
        if (method_exists($handler, 'handleSetLocalPlayerAsInitialized')) {
            return $handler->handleSetLocalPlayerAsInitialized($this);
        }
        return false;
    }

    public function canBeSentBeforeLogin(): bool {
        return false;
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
}