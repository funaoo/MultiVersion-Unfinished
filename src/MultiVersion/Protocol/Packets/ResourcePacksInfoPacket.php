<?php

declare(strict_types=1);

namespace MultiVersion\Protocol\Packets;

use MultiVersion\Protocol\Packets\BasePacket;
use MultiVersion\Utils\BinaryStream;

final class ResourcePacksInfoPacket extends BasePacket {

    public bool $mustAccept;
    public bool $hasScripts;
    public bool $forceAccept;
    public array $behaviorPackEntries = [];
    public array $resourcePackEntries = [];

    public function __construct() {
        $this->packetId = 0x06;
    }

    public function canBeSentBeforeLogin(): bool {
        return true;
    }

    public function encode(): void {
        $stream = $this->getBuffer();
        $this->encodeHeader($stream);

        $stream->putBool($this->mustAccept);
        $stream->putBool($this->hasScripts);
        $stream->putBool($this->forceAccept);

        $this->encodePacks($stream, $this->behaviorPackEntries);
        $this->encodePacks($stream, $this->resourcePackEntries);

        $this->encoded = true;
    }

    public function decode(): void {
        $stream = $this->getBuffer();
        $this->decodeHeader($stream);

        $this->mustAccept = $stream->getBool();
        $this->hasScripts = $stream->getBool();
        $this->forceAccept = $stream->getBool();

        $this->behaviorPackEntries = $this->decodePacks($stream);
        $this->resourcePackEntries = $this->decodePacks($stream);

        $this->decoded = true;
    }

    private function encodePacks(BinaryStream $stream, array $packs): void {
        $stream->putLShort(count($packs));

        foreach ($packs as $pack) {
            $stream->putString($pack['uuid'] ?? '');
            $stream->putString($pack['version'] ?? '');
            $stream->putLLong($pack['size'] ?? 0);
            $stream->putString($pack['contentKey'] ?? '');
            $stream->putString($pack['subPackName'] ?? '');
            $stream->putString($pack['contentIdentity'] ?? '');
            $stream->putBool($pack['hasScripts'] ?? false);
        }
    }

    private function decodePacks(BinaryStream $stream): array {
        $count = $stream->getLShort();
        $packs = [];

        for ($i = 0; $i < $count; $i++) {
            $packs[] = [
                'uuid' => $stream->getString(),
                'version' => $stream->getString(),
                'size' => $stream->getLLong(),
                'contentKey' => $stream->getString(),
                'subPackName' => $stream->getString(),
                'contentIdentity' => $stream->getString(),
                'hasScripts' => $stream->getBool()
            ];
        }

        return $packs;
    }

    public function addBehaviorPack(string $uuid, string $version, int $size, string $contentKey = '', string $subPackName = '', string $contentIdentity = '', bool $hasScripts = false): void {
        $this->behaviorPackEntries[] = [
            'uuid' => $uuid,
            'version' => $version,
            'size' => $size,
            'contentKey' => $contentKey,
            'subPackName' => $subPackName,
            'contentIdentity' => $contentIdentity,
            'hasScripts' => $hasScripts
        ];
    }

    public function addResourcePack(string $uuid, string $version, int $size, string $contentKey = '', string $subPackName = '', string $contentIdentity = '', bool $hasScripts = false): void {
        $this->resourcePackEntries[] = [
            'uuid' => $uuid,
            'version' => $version,
            'size' => $size,
            'contentKey' => $contentKey,
            'subPackName' => $subPackName,
            'contentIdentity' => $contentIdentity,
            'hasScripts' => $hasScripts
        ];
    }

    public function getBehaviorPacks(): array {
        return $this->behaviorPackEntries;
    }

    public function getResourcePacks(): array {
        return $this->resourcePackEntries;
    }

    public function hasBehaviorPacks(): bool {
        return !empty($this->behaviorPackEntries);
    }

    public function hasResourcePacks(): bool {
        return !empty($this->resourcePackEntries);
    }

    public function getTotalPackCount(): int {
        return count($this->behaviorPackEntries) + count($this->resourcePackEntries);
    }

    public function handle(object $handler): bool {
        return $handler->handleResourcePacksInfo($this);
    }
}