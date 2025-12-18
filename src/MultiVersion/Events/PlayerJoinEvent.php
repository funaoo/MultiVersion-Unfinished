<?php

declare(strict_types=1);

namespace MultiVersion\Events;

final class PlayerJoinEvent extends Event {

    private \pocketmine\player\Player $player;
    private int $protocol;
    private string $protocolVersion;
    private array $playerData = [];

    public function __construct(\pocketmine\player\Player $player, int $protocol) {
        parent::__construct();
        $this->player = $player;
        $this->protocol = $protocol;
        $this->protocolVersion = $this->getProtocolVersionString($protocol);
        $this->loadPlayerData();
    }

    private function getProtocolVersionString(int $protocol): string {
        return match($protocol) {
            621 => "1.21.130",
            594 => "1.20.40",
            527 => "1.18.12",
            default => "Unknown"
        };
    }

    private function loadPlayerData(): void {
        $playerInfo = $this->player->getPlayerInfo();
        $extraData = $playerInfo->getExtraData();

        $this->playerData = [
            'uuid' => $this->player->getUniqueId()->toString(),
            'xuid' => $playerInfo->getXuid(),
            'device_model' => $extraData['DeviceModel'] ?? 'Unknown',
            'device_os' => $extraData['DeviceOS'] ?? 'Unknown',
            'game_version' => $extraData['GameVersion'] ?? 'Unknown',
            'ui_profile' => $extraData['UIProfile'] ?? 0
        ];
    }

    public function getPlayer(): \pocketmine\player\Player {
        return $this->player;
    }

    public function getProtocol(): int {
        return $this->protocol;
    }

    public function getProtocolVersion(): string {
        return $this->protocolVersion;
    }

    public function getPlayerData(): array {
        return $this->playerData;
    }

    public function getDeviceModel(): string {
        return $this->playerData['device_model'] ?? 'Unknown';
    }

    public function getDeviceOS(): string {
        return $this->playerData['device_os'] ?? 'Unknown';
    }

    public function getGameVersion(): string {
        return $this->playerData['game_version'] ?? 'Unknown';
    }

    public function getJoinMessage(): string {
        return "§e{$this->player->getName()} joined the game (Protocol: {$this->protocol})";
    }

    public function setJoinMessage(string $message): void {
        $this->playerData['custom_join_message'] = $message;
    }
}