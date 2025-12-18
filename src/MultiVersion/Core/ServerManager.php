<?php

declare(strict_types=1);

namespace MultiVersion\Core;

use MultiVersion\MultiVersion;

final class ServerManager {

    private MultiVersion $plugin;
    private int $totalConnections = 0;
    private int $translationErrors = 0;
    private int $serverProtocol = 621;

    public function __construct(MultiVersion $plugin){
        $this->plugin = $plugin;
    }

    public function incrementTotalConnections(): void{
        $this->totalConnections++;
    }

    public function incrementTranslationErrors(): void{
        $this->translationErrors++;
    }

    public function getStatistics(): array{
        return [
            'total_connections' => $this->totalConnections,
            'active_sessions' => $this->plugin->getVersionRegistry()->getActiveSessionCount(),
            'packets_routed' => $this->plugin->getPacketRouter()->getPacketsRouted(),
            'translation_errors' => $this->translationErrors
        ];
    }

    public function getServerProtocol(): int{
        return $this->serverProtocol;
    }

    public function saveStatistics(): void{
        $stats = $this->getStatistics();
        $file = $this->plugin->getDataFolder() . "statistics.json";
        file_put_contents($file, json_encode($stats, JSON_PRETTY_PRINT));
    }
}
