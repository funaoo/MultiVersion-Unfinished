<?php

declare(strict_types=1);

namespace MultiVersion\Core;

use MultiVersion\MultiVersion;
use MultiVersion\Protocol\ProtocolInterface;
use MultiVersion\Protocol\Versions\Protocol527;
use MultiVersion\Protocol\Versions\Protocol594;
use MultiVersion\Protocol\Versions\Protocol621;

final class VersionRegistry{

    private MultiVersion $plugin;
    private array $protocols = [];
    private array $sessions = [];

    public function __construct(MultiVersion $plugin){
        $this->plugin = $plugin;
        $this->registerProtocols();
    }

    private function registerProtocols(): void{
        $this->protocols = [
            621 => new Protocol621(),
            594 => new Protocol594(),
            527 => new Protocol527()
        ];
    }

    public function register(string $playerName, int $protocol): void{
        if(!$this->isProtocolSupported($protocol)){
            return;
        }

        $this->sessions[$playerName] = [
            "protocol" => $protocol,
            "time" => microtime(true)
        ];
    }

    public function unregister(string $playerName): void{
        unset($this->sessions[$playerName]);
    }

    public function getProtocol(string $playerName): ?int{
        return $this->sessions[$playerName]["protocol"] ?? null;
    }

    public function getProtocolInterface(int $protocol): ?ProtocolInterface{
        return $this->protocols[$protocol] ?? null;
    }

    public function isProtocolSupported(int $protocol): bool{
        return isset($this->protocols[$protocol]);
    }

    public function getSupportedProtocols(): array{
        return array_keys($this->protocols);
    }

    public function getActiveSessions(): array{
        return $this->sessions;
    }

    public function getActiveSessionCount(): int{
        return count($this->sessions);
    }
}
