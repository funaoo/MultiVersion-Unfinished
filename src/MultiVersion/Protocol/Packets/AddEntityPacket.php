<?php

declare(strict_types=1);

namespace MultiVersion\Protocol\Packets;

use MultiVersion\Protocol\Packets\BasePacket;
use MultiVersion\Utils\BinaryStream;

final class AddEntityPacket extends BasePacket {

    public int $entityUniqueId;
    public int $entityRuntimeId;
    public string $entityType;
    public object $position;
    public object $motion;
    public float $pitch;
    public float $yaw;
    public float $headYaw;
    public float $bodyYaw;
    public array $attributes = [];
    public array $metadata = [];
    public array $links = [];

    public function __construct() {
        $this->packetId = 0x0d;
    }

    public function encode(): void {
        $stream = $this->getBuffer();
        $this->encodeHeader($stream);

        $this->encodeEntityUniqueId($stream, $this->entityUniqueId);
        $this->encodeEntityRuntimeId($stream, $this->entityRuntimeId);
        $stream->putString($this->entityType);
        $this->encodeVector3($stream, $this->position);
        $this->encodeVector3($stream, $this->motion);
        $stream->putLFloat($this->pitch);
        $stream->putLFloat($this->yaw);
        $stream->putLFloat($this->headYaw);
        $stream->putLFloat($this->bodyYaw);
        $this->encodeAttributes($stream);
        $this->encodeMetadata($stream);
        $this->encodeLinks($stream);

        $this->encoded = true;
    }

    public function decode(): void {
        $stream = $this->getBuffer();
        $this->decodeHeader($stream);

        $this->entityUniqueId = $this->decodeEntityUniqueId($stream);
        $this->entityRuntimeId = $this->decodeEntityRuntimeId($stream);
        $this->entityType = $stream->getString();
        $this->position = $this->decodeVector3($stream);
        $this->motion = $this->decodeVector3($stream);
        $this->pitch = $stream->getLFloat();
        $this->yaw = $stream->getLFloat();
        $this->headYaw = $stream->getLFloat();
        $this->bodyYaw = $stream->getLFloat();
        $this->decodeAttributes($stream);
        $this->decodeMetadata($stream);
        $this->decodeLinks($stream);

        $this->decoded = true;
    }

    private function encodeAttributes(BinaryStream $stream): void {
        $stream->putUnsignedVarInt(count($this->attributes));

        foreach ($this->attributes as $attribute) {
            $stream->putLFloat($attribute['min']);
            $stream->putLFloat($attribute['max']);
            $stream->putLFloat($attribute['current']);
            $stream->putLFloat($attribute['default']);
            $stream->putString($attribute['name']);
        }
    }

    private function decodeAttributes(BinaryStream $stream): void {
        $count = $stream->getUnsignedVarInt();
        $this->attributes = [];

        for ($i = 0; $i < $count; $i++) {
            $this->attributes[] = [
                'min' => $stream->getLFloat(),
                'max' => $stream->getLFloat(),
                'current' => $stream->getLFloat(),
                'default' => $stream->getLFloat(),
                'name' => $stream->getString()
            ];
        }
    }

    private function encodeMetadata(BinaryStream $stream): void {
        $stream->putUnsignedVarInt(count($this->metadata));

        foreach ($this->metadata as $key => $entry) {
            $stream->putUnsignedVarInt($key);
            $stream->putUnsignedVarInt($entry['type']);

            match($entry['type']) {
                0 => $stream->putByte($entry['value']),
                1 => $stream->putLShort($entry['value']),
                2 => $stream->putVarInt($entry['value']),
                3 => $stream->putLFloat($entry['value']),
                4 => $stream->putString($entry['value']),
                5 => $this->encodeItemStack($stream, $entry['value']),
                6 => $this->encodeBlockPosition($stream, $entry['value']),
                7 => $stream->putLLong($entry['value']),
                8 => $this->encodeVector3($stream, $entry['value']),
                default => null
            };
        }
    }

    private function decodeMetadata(BinaryStream $stream): void {
        $count = $stream->getUnsignedVarInt();
        $this->metadata = [];

        for ($i = 0; $i < $count; $i++) {
            $key = $stream->getUnsignedVarInt();
            $type = $stream->getUnsignedVarInt();

            $value = match($type) {
                0 => $stream->getByte(),
                1 => $stream->getLShort(),
                2 => $stream->getVarInt(),
                3 => $stream->getLFloat(),
                4 => $stream->getString(),
                5 => $this->decodeItemStack($stream),
                6 => $this->decodeBlockPosition($stream),
                7 => $stream->getLLong(),
                8 => $this->decodeVector3($stream),
                default => null
            };

            $this->metadata[$key] = ['type' => $type, 'value' => $value];
        }
    }

    private function encodeLinks(BinaryStream $stream): void {
        $stream->putUnsignedVarInt(count($this->links));

        foreach ($this->links as $link) {
            $this->encodeEntityRuntimeId($stream, $link['from']);
            $this->encodeEntityRuntimeId($stream, $link['to']);
            $stream->putByte($link['type']);
            $stream->putBool($link['immediate']);
            $stream->putBool($link['rider_initiated']);
        }
    }

    private function decodeLinks(BinaryStream $stream): void {
        $count = $stream->getUnsignedVarInt();
        $this->links = [];

        for ($i = 0; $i < $count; $i++) {
            $this->links[] = [
                'from' => $this->decodeEntityRuntimeId($stream),
                'to' => $this->decodeEntityRuntimeId($stream),
                'type' => $stream->getByte(),
                'immediate' => $stream->getBool(),
                'rider_initiated' => $stream->getBool()
            ];
        }
    }

    private function encodeItemStack(BinaryStream $stream, object $item): void {
        $stream->putVarInt($item->id ?? 0);

        if (($item->id ?? 0) === 0) {
            return;
        }

        $stream->putVarInt($item->count ?? 1);
        $stream->putUnsignedVarInt($item->meta ?? 0);
    }

    private function decodeItemStack(BinaryStream $stream): object {
        $item = new \stdClass();
        $item->id = $stream->getVarInt();

        if ($item->id === 0) {
            return $item;
        }

        $item->count = $stream->getVarInt();
        $item->meta = $stream->getUnsignedVarInt();

        return $item;
    }

    public function getEntityType(): string {
        return $this->entityType;
    }

    public function getEntityUniqueId(): int {
        return $this->entityUniqueId;
    }

    public function getEntityRuntimeId(): int {
        return $this->entityRuntimeId;
    }

    public function getPosition(): object {
        return $this->position;
    }

    public function getMotion(): object {
        return $this->motion;
    }

    public function getAttributes(): array {
        return $this->attributes;
    }

    public function getMetadata(): array {
        return $this->metadata;
    }

    public function getLinks(): array {
        return $this->links;
    }

    public function addAttribute(string $name, float $min, float $max, float $current, float $default): void {
        $this->attributes[] = [
            'name' => $name,
            'min' => $min,
            'max' => $max,
            'current' => $current,
            'default' => $default
        ];
    }

    public function addMetadata(int $key, int $type, mixed $value): void {
        $this->metadata[$key] = [
            'type' => $type,
            'value' => $value
        ];
    }

    public function addLink(int $from, int $to, int $type, bool $immediate = false, bool $riderInitiated = false): void {
        $this->links[] = [
            'from' => $from,
            'to' => $to,
            'type' => $type,
            'immediate' => $immediate,
            'rider_initiated' => $riderInitiated
        ];
    }

    public static function create(int $entityUniqueId, int $entityRuntimeId, string $entityType, object $position): self {
        $packet = new self();
        $packet->entityUniqueId = $entityUniqueId;
        $packet->entityRuntimeId = $entityRuntimeId;
        $packet->entityType = $entityType;
        $packet->position = $position;
        $packet->motion = new \stdClass();
        $packet->motion->x = 0.0;
        $packet->motion->y = 0.0;
        $packet->motion->z = 0.0;
        $packet->pitch = 0.0;
        $packet->yaw = 0.0;
        $packet->headYaw = 0.0;
        $packet->bodyYaw = 0.0;
        $packet->attributes = [];
        $packet->metadata = [];
        $packet->links = [];
        return $packet;
    }

    public function handle(object $handler): bool {
        return $handler->handleAddEntity($this);
    }
}