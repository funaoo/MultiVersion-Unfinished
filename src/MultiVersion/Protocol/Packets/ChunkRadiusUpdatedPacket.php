<?php
declare(strict_types=1);

namespace MultiVersion\Protocol\Packets;

use MultiVersion\Protocol\Packets\BasePacket;

class ChunkRadiusUpdatedPacket extends BasePacket {
    protected int $packetId = 0x46;
    public int $radius;

    public function encode(): void {
        $stream = $this->getBuffer();
        $this->encodeHeader($stream);
        $stream->putVarInt($this->radius);
        $this->encoded = true;
    }

    public function decode(): void {
        $stream = $this->getBuffer();
        $this->decodeHeader($stream);
        $this->radius = $stream->getVarInt();
        $this->decoded = true;
    }

    public function handle(object $handler): bool {
        if (method_exists($handler, 'handleChunkRadiusUpdated')) {
            return $handler->handleChunkRadiusUpdated($this);
        }
        return false;
    }

    public function canBeSentBeforeLogin(): bool {
        return false;
    }

    public function getRadius(): int {
        return $this->radius;
    }

    public function setRadius(int $radius): void {
        if ($radius < 0 || $radius > 96) {
            throw new \InvalidArgumentException("Chunk radius must be between 0 and 96, got {$radius}");
        }
        $this->radius = $radius;
    }
}