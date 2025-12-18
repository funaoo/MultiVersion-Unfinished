<?php

declare(strict_types=1);

namespace MultiVersion\Protocol\Packets;

use MultiVersion\Protocol\Packets\BasePacket;

final class LoginPacket extends BasePacket {

    public int $protocol;
    public string $chainDataJson;
    public string $clientDataJson;
    public array $chainData = [];
    public array $clientData = [];

    public function __construct() {
        $this->packetId = 0x01;
    }

    public function canBeSentBeforeLogin(): bool {
        return true;
    }

    public function encode(): void {
        $stream = $this->getBuffer();
        $this->encodeHeader($stream);

        $stream->putInt($this->protocol);

        $chainDataLength = strlen($this->chainDataJson);
        $clientDataLength = strlen($this->clientDataJson);
        $totalLength = $chainDataLength + $clientDataLength + 8;

        $stream->putLInt($totalLength);
        $stream->putLInt($chainDataLength);
        $stream->put($this->chainDataJson);
        $stream->putLInt($clientDataLength);
        $stream->put($this->clientDataJson);

        $this->encoded = true;
    }

    public function decode(): void {
        $stream = $this->getBuffer();
        $this->decodeHeader($stream);

        $this->protocol = $stream->getInt();

        $totalLength = $stream->getLInt();
        $chainDataLength = $stream->getLInt();
        $this->chainDataJson = $stream->get($chainDataLength);
        $clientDataLength = $stream->getLInt();
        $this->clientDataJson = $stream->get($clientDataLength);

        $this->parseChainData();
        $this->parseClientData();

        $this->decoded = true;
    }

    private function parseChainData(): void {
        try {
            $data = json_decode($this->chainDataJson, true);
            $this->chainData = $data ?? [];
        } catch (\Exception $e) {
            $this->chainData = [];
        }
    }

    private function parseClientData(): void {
        try {
            $data = json_decode($this->clientDataJson, true);
            $this->clientData = $data ?? [];
        } catch (\Exception $e) {
            $this->clientData = [];
        }
    }

    public function getUsername(): ?string {
        return $this->clientData['displayName'] ?? null;
    }

    public function getUUID(): ?string {
        if (isset($this->chainData['chain'])) {
            foreach ($this->chainData['chain'] as $jwt) {
                $parts = explode('.', $jwt);
                if (count($parts) === 3) {
                    $payload = json_decode(base64_decode($parts[1]), true);
                    if (isset($payload['extraData']['identity'])) {
                        return $payload['extraData']['identity'];
                    }
                }
            }
        }
        return null;
    }

    public function getXUID(): ?string {
        if (isset($this->chainData['chain'])) {
            foreach ($this->chainData['chain'] as $jwt) {
                $parts = explode('.', $jwt);
                if (count($parts) === 3) {
                    $payload = json_decode(base64_decode($parts[1]), true);
                    if (isset($payload['extraData']['XUID'])) {
                        return $payload['extraData']['XUID'];
                    }
                }
            }
        }
        return null;
    }

    public function getDeviceModel(): ?string {
        return $this->clientData['DeviceModel'] ?? null;
    }

    public function getDeviceOS(): int {
        return $this->clientData['DeviceOS'] ?? 0;
    }

    public function getGameVersion(): ?string {
        return $this->clientData['GameVersion'] ?? null;
    }

    public function getServerAddress(): ?string {
        return $this->clientData['ServerAddress'] ?? null;
    }

    public function getLanguageCode(): ?string {
        return $this->clientData['LanguageCode'] ?? null;
    }

    public function getSkinId(): ?string {
        return $this->clientData['SkinId'] ?? null;
    }

    public function getSkinData(): ?string {
        return $this->clientData['SkinData'] ?? null;
    }

    public function getSkinGeometryData(): ?string {
        return $this->clientData['SkinGeometryData'] ?? null;
    }

    public function getCurrentInputMode(): int {
        return $this->clientData['CurrentInputMode'] ?? 0;
    }

    public function getDefaultInputMode(): int {
        return $this->clientData['DefaultInputMode'] ?? 0;
    }

    public function getUIProfile(): int {
        return $this->clientData['UIProfile'] ?? 0;
    }

    public function getClientRandomId(): ?int {
        return $this->clientData['ClientRandomId'] ?? null;
    }

    public function getPlatformOnlineId(): ?string {
        return $this->clientData['PlatformOnlineId'] ?? null;
    }

    public function getThirdPartyName(): ?string {
        return $this->clientData['ThirdPartyName'] ?? null;
    }

    public function isTrustedSkin(): bool {
        return $this->clientData['TrustedSkin'] ?? false;
    }

    public function isPremiumSkin(): bool {
        return $this->clientData['PremiumSkin'] ?? false;
    }

    public function isPersonaSkin(): bool {
        return $this->clientData['PersonaSkin'] ?? false;
    }

    public function handle(object $handler): bool {
        return $handler->handleLogin($this);
    }
}