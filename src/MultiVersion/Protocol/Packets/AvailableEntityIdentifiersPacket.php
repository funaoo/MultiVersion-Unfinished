<?php
declare(strict_types=1);

namespace MultiVersion\Protocol\Packets;

use MultiVersion\Protocol\Packets\BasePacket;
use MultiVersion\Utils\BinaryStream;

class AvailableEntityIdentifiersPacket extends BasePacket {
    protected int $packetId = 0x77;
    public string $namedTag;

    public function encode(): void {
        $stream = $this->getBuffer();
        $this->encodeHeader($stream);
        $stream->put($this->namedTag);
        $this->encoded = true;
    }

    public function decode(): void {
        $stream = $this->getBuffer();
        $this->decodeHeader($stream);
        $this->namedTag = $stream->getRemaining();
        $this->decoded = true;
    }

    public function handle(object $handler): bool {
        if (method_exists($handler, 'handleAvailableEntityIdentifiers')) {
            return $handler->handleAvailableEntityIdentifiers($this);
        }
        return false;
    }

    public function canBeSentBeforeLogin(): bool {
        return true;
    }

    public function getNamedTag(): string {
        return $this->namedTag;
    }

    public function setNamedTag(string $tag): void {
        if (empty($tag)) {
            throw new \InvalidArgumentException("Named tag cannot be empty");
        }
        $this->namedTag = $tag;
    }

    public function setFromJson(string $jsonData): void {
        try {
            $decoded = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($decoded)) {
                throw new \InvalidArgumentException("JSON data must decode to an array");
            }
            $this->namedTag = $this->encodeNbt($decoded);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException("Invalid JSON data: " . $e->getMessage(), 0, $e);
        }
    }

    private function encodeNbt(array $data): string {
        $stream = new BinaryStream();
        $stream->putByte(10);
        $stream->putString("");
        $this->writeList($stream, $data);
        return $stream->getBuffer();
    }

    private function writeList(BinaryStream $stream, array $data): void {
        if (isset($data['idlist']) && is_array($data['idlist'])) {
            $stream->putByte(9);
            $stream->putString("idlist");
            $stream->putByte(10);
            $stream->putLInt(count($data['idlist']));

            foreach ($data['idlist'] as $entity) {
                $this->writeCompound($stream, $entity);
            }
        }
        $stream->putByte(0);
    }

    private function writeCompound(BinaryStream $stream, array $data): void {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $stream->putByte(8);
                $stream->putString($key);
                $stream->putString($value);
            } elseif (is_int($value)) {
                $stream->putByte(3);
                $stream->putString($key);
                $stream->putLInt($value);
            } elseif (is_bool($value)) {
                $stream->putByte(1);
                $stream->putString($key);
                $stream->putByte($value ? 1 : 0);
            }
        }
        $stream->putByte(0);
    }
}