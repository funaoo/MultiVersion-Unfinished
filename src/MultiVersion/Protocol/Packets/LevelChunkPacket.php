<?php

declare(strict_types=1);

namespace MultiVersion\Protocol\Packets;

use MultiVersion\Protocol\Packets\BasePacket;

final class LevelChunkPacket extends BasePacket {

    public int $chunkX;
    public int $chunkZ;
    public int $subChunkCount;
    public bool $cacheEnabled;
    public array $blobHashes = [];
    public string $data;
    public int $dimension;

    public function __construct() {
        $this->packetId = 0x3a;
    }

    public function encode(): void {
        $stream = $this->getBuffer();
        $this->encodeHeader($stream);

        $stream->putVarInt($this->chunkX);
        $stream->putVarInt($this->chunkZ);
        $stream->putUnsignedVarInt($this->subChunkCount);
        $stream->putBool($this->cacheEnabled);

        if ($this->cacheEnabled) {
            $stream->putUnsignedVarInt(count($this->blobHashes));
            foreach ($this->blobHashes as $hash) {
                $stream->putLLong($hash);
            }
        }

        $stream->putString($this->data);

        $this->encoded = true;
    }

    public function decode(): void {
        $stream = $this->getBuffer();
        $this->decodeHeader($stream);

        $this->chunkX = $stream->getVarInt();
        $this->chunkZ = $stream->getVarInt();
        $this->subChunkCount = $stream->getUnsignedVarInt();
        $this->cacheEnabled = $stream->getBool();

        if ($this->cacheEnabled) {
            $count = $stream->getUnsignedVarInt();
            $this->blobHashes = [];
            for ($i = 0; $i < $count; $i++) {
                $this->blobHashes[] = $stream->getLLong();
            }
        }

        $this->data = $stream->getString();

        $this->decoded = true;
    }

    public function getChunkX(): int {
        return $this->chunkX;
    }

    public function getChunkZ(): int {
        return $this->chunkZ;
    }

    public function getSubChunkCount(): int {
        return $this->subChunkCount;
    }

    public function isCacheEnabled(): bool {
        return $this->cacheEnabled;
    }

    public function getBlobHashes(): array {
        return $this->blobHashes;
    }

    public function getData(): string {
        return $this->data;
    }

    public function getChunkHash(): int {
        return ($this->chunkX << 32) | ($this->chunkZ & 0xFFFFFFFF);
    }

    public function handle(object $handler): bool {
        return $handler->handleLevelChunk($this);
    }
}