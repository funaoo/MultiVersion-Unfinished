<?php

declare(strict_types=1);

namespace MultiVersion\Protocol\Packets;

use MultiVersion\Protocol\Packets\BasePacket;
use MultiVersion\Utils\BinaryStream;

final class SetEntityDataPacket extends BasePacket {

    public const DATA_TYPE_BYTE = 0;
    public const DATA_TYPE_SHORT = 1;
    public const DATA_TYPE_INT = 2;
    public const DATA_TYPE_FLOAT = 3;
    public const DATA_TYPE_STRING = 4;
    public const DATA_TYPE_COMPOUND_TAG = 5;
    public const DATA_TYPE_BLOCK_POS = 6;
    public const DATA_TYPE_LONG = 7;
    public const DATA_TYPE_VECTOR3 = 8;

    public const DATA_FLAGS = 0;
    public const DATA_HEALTH = 1;
    public const DATA_VARIANT = 2;
    public const DATA_COLOR = 3;
    public const DATA_NAMETAG = 4;
    public const DATA_OWNER_EID = 5;
    public const DATA_TARGET_EID = 6;
    public const DATA_AIR = 7;
    public const DATA_POTION_COLOR = 8;
    public const DATA_POTION_AMBIENT = 9;
    public const DATA_HURT_TIME = 11;
    public const DATA_HURT_DIRECTION = 12;
    public const DATA_SCALE = 39;
    public const DATA_BOUNDING_BOX_WIDTH = 54;
    public const DATA_BOUNDING_BOX_HEIGHT = 55;

    public int $entityRuntimeId;
    public array $metadata = [];
    public array $properties = [];
    public int $tick;

    public function __construct() {
        $this->packetId = 0x27;
    }

    public function encode(): void {
        $stream = $this->getBuffer();
        $this->encodeHeader($stream);

        $this->encodeEntityRuntimeId($stream, $this->entityRuntimeId);
        $this->encodeMetadata($stream, $this->metadata);
        $this->encodeProperties($stream, $this->properties);
        $stream->putUnsignedVarLong($this->tick);

        $this->encoded = true;
    }

    public function decode(): void {
        $stream = $this->getBuffer();
        $this->decodeHeader($stream);

        $this->entityRuntimeId = $this->decodeEntityRuntimeId($stream);
        $this->metadata = $this->decodeMetadata($stream);
        $this->properties = $this->decodeProperties($stream);
        $this->tick = $stream->getUnsignedVarLong();

        $this->decoded = true;
    }

    private function encodeMetadata(BinaryStream $stream, array $metadata): void {
        $stream->putUnsignedVarInt(count($metadata));

        foreach ($metadata as $key => $entry) {
            $stream->putUnsignedVarInt($key);
            $stream->putUnsignedVarInt($entry['type']);

            match($entry['type']) {
                self::DATA_TYPE_BYTE => $stream->putByte($entry['value']),
                self::DATA_TYPE_SHORT => $stream->putLShort($entry['value']),
                self::DATA_TYPE_INT => $stream->putVarInt($entry['value']),
                self::DATA_TYPE_FLOAT => $stream->putLFloat($entry['value']),
                self::DATA_TYPE_STRING => $stream->putString($entry['value']),
                self::DATA_TYPE_COMPOUND_TAG => $this->encodeCompoundTag($stream, $entry['value']),
                self::DATA_TYPE_BLOCK_POS => $this->encodeBlockPosition($stream, $entry['value']),
                self::DATA_TYPE_LONG => $stream->putVarLong($entry['value']),
                self::DATA_TYPE_VECTOR3 => $this->encodeVector3($stream, $entry['value']),
                default => null
            };
        }
    }

    private function decodeMetadata(BinaryStream $stream): array {
        $count = $stream->getUnsignedVarInt();
        $metadata = [];

        for ($i = 0; $i < $count; $i++) {
            $key = $stream->getUnsignedVarInt();
            $type = $stream->getUnsignedVarInt();

            $value = match($type) {
                self::DATA_TYPE_BYTE => $stream->getByte(),
                self::DATA_TYPE_SHORT => $stream->getLShort(),
                self::DATA_TYPE_INT => $stream->getVarInt(),
                self::DATA_TYPE_FLOAT => $stream->getLFloat(),
                self::DATA_TYPE_STRING => $stream->getString(),
                self::DATA_TYPE_COMPOUND_TAG => $this->decodeCompoundTag($stream),
                self::DATA_TYPE_BLOCK_POS => $this->decodeBlockPosition($stream),
                self::DATA_TYPE_LONG => $stream->getVarLong(),
                self::DATA_TYPE_VECTOR3 => $this->decodeVector3($stream),
                default => null
            };

            $metadata[$key] = ['type' => $type, 'value' => $value];
        }

        return $metadata;
    }

    private function encodeProperties(BinaryStream $stream, array $properties): void {
        $stream->putUnsignedVarInt(count($properties));

        foreach ($properties as $property) {
            $stream->putString($property['key']);
            $stream->putLInt($property['type']);

            match($property['type']) {
                0 => $stream->putBool($property['value']),
                1 => $stream->putVarInt($property['value']),
                2 => $stream->putLFloat($property['value']),
                default => null
            };
        }
    }

    private function decodeProperties(BinaryStream $stream): array {
        $count = $stream->getUnsignedVarInt();
        $properties = [];

        for ($i = 0; $i < $count; $i++) {
            $property = [];
            $property['key'] = $stream->getString();
            $property['type'] = $stream->getLInt();

            $property['value'] = match($property['type']) {
                0 => $stream->getBool(),
                1 => $stream->getVarInt(),
                2 => $stream->getLFloat(),
                default => null
            };

            $properties[] = $property;
        }

        return $properties;
    }

    private function encodeCompoundTag(BinaryStream $stream, mixed $value): void {
        if (is_string($value)) {
            $stream->putLShort(strlen($value));
            $stream->put($value);
        } else {
            $stream->putLShort(0);
        }
    }

    private function decodeCompoundTag(BinaryStream $stream): string {
        $length = $stream->getLShort();
        return $length > 0 ? $stream->get($length) : '';
    }

    public function getEntityRuntimeId(): int {
        return $this->entityRuntimeId;
    }

    public function setEntityRuntimeId(int $id): void {
        $this->entityRuntimeId = $id;
    }

    public function getMetadata(): array {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): void {
        $this->metadata = $metadata;
    }

    public function getProperties(): array {
        return $this->properties;
    }

    public function setProperties(array $properties): void {
        $this->properties = $properties;
    }

    public function getTick(): int {
        return $this->tick;
    }

    public function setTick(int $tick): void {
        $this->tick = $tick;
    }

    public function putByte(int $key, int $value): void {
        $this->metadata[$key] = ['type' => self::DATA_TYPE_BYTE, 'value' => $value];
    }

    public function putShort(int $key, int $value): void {
        $this->metadata[$key] = ['type' => self::DATA_TYPE_SHORT, 'value' => $value];
    }

    public function putInt(int $key, int $value): void {
        $this->metadata[$key] = ['type' => self::DATA_TYPE_INT, 'value' => $value];
    }

    public function putFloat(int $key, float $value): void {
        $this->metadata[$key] = ['type' => self::DATA_TYPE_FLOAT, 'value' => $value];
    }

    public function putString(int $key, string $value): void {
        $this->metadata[$key] = ['type' => self::DATA_TYPE_STRING, 'value' => $value];
    }

    public function putLong(int $key, int $value): void {
        $this->metadata[$key] = ['type' => self::DATA_TYPE_LONG, 'value' => $value];
    }

    public function putVector3(int $key, object $value): void {
        $this->metadata[$key] = ['type' => self::DATA_TYPE_VECTOR3, 'value' => $value];
    }

    public function putBlockPos(int $key, object $value): void {
        $this->metadata[$key] = ['type' => self::DATA_TYPE_BLOCK_POS, 'value' => $value];
    }

    public function addProperty(string $key, int $type, mixed $value): void {
        $this->properties[] = [
            'key' => $key,
            'type' => $type,
            'value' => $value
        ];
    }

    public function hasMetadata(int $key): bool {
        return isset($this->metadata[$key]);
    }

    public function getMetadataValue(int $key): mixed {
        return $this->metadata[$key]['value'] ?? null;
    }

    public function removeMetadata(int $key): void {
        unset($this->metadata[$key]);
    }

    public static function create(int $entityRuntimeId, array $metadata = [], int $tick = 0): self {
        $packet = new self();
        $packet->entityRuntimeId = $entityRuntimeId;
        $packet->metadata = $metadata;
        $packet->properties = [];
        $packet->tick = $tick;
        return $packet;
    }

    public function handle(object $handler): bool {
        return $handler->handleSetEntityData($this);
    }
}