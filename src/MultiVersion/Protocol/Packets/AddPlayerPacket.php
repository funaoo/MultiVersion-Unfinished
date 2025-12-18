<?php

declare(strict_types=1);

namespace MultiVersion\Protocol\Packets;

use MultiVersion\Protocol\Packets\BasePacket;
use MultiVersion\Utils\BinaryStream;

final class AddPlayerPacket extends BasePacket {

    public string $uuid;
    public string $username;
    public int $entityUniqueId;
    public int $entityRuntimeId;
    public string $platformChatId;
    public object $position;
    public object $motion;
    public float $pitch;
    public float $yaw;
    public float $headYaw;
    public object $heldItem;
    public array $metadata = [];
    public int $deviceId;
    public int $buildPlatform;

    public function __construct() {
        $this->packetId = 0x0c;
    }

    public function encode(): void {
        $stream = $this->getBuffer();
        $this->encodeHeader($stream);

        $this->encodeUUID($stream, $this->uuid);
        $stream->putString($this->username);
        $this->encodeEntityUniqueId($stream, $this->entityUniqueId);
        $this->encodeEntityRuntimeId($stream, $this->entityRuntimeId);
        $stream->putString($this->platformChatId);
        $this->encodeVector3($stream, $this->position);
        $this->encodeVector3($stream, $this->motion);
        $stream->putLFloat($this->pitch);
        $stream->putLFloat($this->yaw);
        $stream->putLFloat($this->headYaw);
        $this->encodeItemStack($stream, $this->heldItem);
        $this->encodeMetadata($stream, $this->metadata);
        $stream->putUnsignedVarInt($this->deviceId);
        $stream->putLInt($this->buildPlatform);

        $this->encoded = true;
    }

    public function decode(): void {
        $stream = $this->getBuffer();
        $this->decodeHeader($stream);

        $this->uuid = $this->decodeUUID($stream);
        $this->username = $stream->getString();
        $this->entityUniqueId = $this->decodeEntityUniqueId($stream);
        $this->entityRuntimeId = $this->decodeEntityRuntimeId($stream);
        $this->platformChatId = $stream->getString();
        $this->position = $this->decodeVector3($stream);
        $this->motion = $this->decodeVector3($stream);
        $this->pitch = $stream->getLFloat();
        $this->yaw = $stream->getLFloat();
        $this->headYaw = $stream->getLFloat();
        $this->heldItem = $this->decodeItemStack($stream);
        $this->metadata = $this->decodeMetadata($stream);
        $this->deviceId = $stream->getUnsignedVarInt();
        $this->buildPlatform = $stream->getLInt();

        $this->decoded = true;
    }

    private function encodeItemStack(BinaryStream $stream, object $item): void {
        $stream->putVarInt($item->id ?? 0);

        if (($item->id ?? 0) === 0) {
            return;
        }

        $stream->putVarInt($item->count ?? 1);
        $stream->putUnsignedVarInt($item->meta ?? 0);
        $stream->putBool(isset($item->nbt));

        if (isset($item->nbt)) {
            $stream->putLShort(strlen($item->nbt));
            $stream->put($item->nbt);
        }

        $stream->putVarInt(count($item->canPlaceOn ?? []));
        foreach ($item->canPlaceOn ?? [] as $block) {
            $stream->putString($block);
        }

        $stream->putVarInt(count($item->canDestroy ?? []));
        foreach ($item->canDestroy ?? [] as $block) {
            $stream->putString($block);
        }
    }

    private function decodeItemStack(BinaryStream $stream): object {
        $item = new \stdClass();
        $item->id = $stream->getVarInt();

        if ($item->id === 0) {
            return $item;
        }

        $item->count = $stream->getVarInt();
        $item->meta = $stream->getUnsignedVarInt();
        $hasNbt = $stream->getBool();

        if ($hasNbt) {
            $nbtLength = $stream->getLShort();
            $item->nbt = $stream->get($nbtLength);
        }

        $canPlaceOnCount = $stream->getVarInt();
        $item->canPlaceOn = [];
        for ($i = 0; $i < $canPlaceOnCount; $i++) {
            $item->canPlaceOn[] = $stream->getString();
        }

        $canDestroyCount = $stream->getVarInt();
        $item->canDestroy = [];
        for ($i = 0; $i < $canDestroyCount; $i++) {
            $item->canDestroy[] = $stream->getString();
        }

        return $item;
    }

    private function encodeMetadata(BinaryStream $stream, array $metadata): void {
        $stream->putUnsignedVarInt(count($metadata));

        foreach ($metadata as $key => $entry) {
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

    private function decodeMetadata(BinaryStream $stream): array {
        $count = $stream->getUnsignedVarInt();
        $metadata = [];

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

            $metadata[$key] = ['type' => $type, 'value' => $value];
        }

        return $metadata;
    }

    public function getUUID(): string {
        return $this->uuid;
    }

    public function getUsername(): string {
        return $this->username;
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

    public function getHeldItem(): object {
        return $this->heldItem;
    }

    public function getMetadata(): array {
        return $this->metadata;
    }

    public static function create(string $uuid, string $username, int $entityUniqueId, int $entityRuntimeId, object $position): self {
        $packet = new self();
        $packet->uuid = $uuid;
        $packet->username = $username;
        $packet->entityUniqueId = $entityUniqueId;
        $packet->entityRuntimeId = $entityRuntimeId;
        $packet->platformChatId = '';
        $packet->position = $position;
        $packet->motion = new \stdClass();
        $packet->motion->x = 0.0;
        $packet->motion->y = 0.0;
        $packet->motion->z = 0.0;
        $packet->pitch = 0.0;
        $packet->yaw = 0.0;
        $packet->headYaw = 0.0;
        $packet->heldItem = new \stdClass();
        $packet->heldItem->id = 0;
        $packet->metadata = [];
        $packet->deviceId = 0;
        $packet->buildPlatform = -1;
        return $packet;
    }

    public function handle(object $handler): bool {
        return $handler->handleAddPlayer($this);
    }
}