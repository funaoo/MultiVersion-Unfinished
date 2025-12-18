<?php

declare(strict_types=1);

namespace MultiVersion\Events;

final class PlayerQuitEvent extends Event {

    private \pocketmine\player\Player $player;
    private int $protocol;
    private string $quitReason;
    private float $playTime;
    private array $statistics = [];

    public function __construct(\pocketmine\player\Player $player, int $protocol, string $quitReason = "disconnect") {
        parent::__construct();
        $this->player = $player;
        $this->protocol = $protocol;
        $this->quitReason = $quitReason;
        $this->calculatePlayTime();
        $this->loadStatistics();
    }

    private function calculatePlayTime(): void {
        $firstPlayed = $this->player->getFirstPlayed();
        $lastPlayed = $this->player->getLastPlayed();

        if ($firstPlayed !== null && $lastPlayed !== null) {
            $this->playTime = ($lastPlayed - $firstPlayed) / 1000;
        } else {
            $this->playTime = 0.0;
        }
    }

    private function loadStatistics(): void {
        $this->statistics = [
            'packets_sent' => 0,
            'packets_received' => 0,
            'chunks_loaded' => 0,
            'session_duration' => $this->playTime
        ];
    }

    public function getPlayer(): \pocketmine\player\Player {
        return $this->player;
    }

    public function getProtocol(): int {
        return $this->protocol;
    }

    public function getQuitReason(): string {
        return $this->quitReason;
    }

    public function setQuitReason(string $reason): void {
        $this->quitReason = $reason;
    }

    public function getPlayTime(): float {
        return $this->playTime;
    }

    public function getStatistics(): array {
        return $this->statistics;
    }

    public function setStatistic(string $key, mixed $value): void {
        $this->statistics[$key] = $value;
    }

    public function getQuitMessage(): string {
        return "§e{$this->player->getName()} left the game";
    }

    public function setQuitMessage(string $message): void {
        $this->statistics['custom_quit_message'] = $message;
    }
}