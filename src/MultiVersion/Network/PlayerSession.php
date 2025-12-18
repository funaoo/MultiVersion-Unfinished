<?php
declare(strict_types=1);

namespace MultiVersion\Network;

use MultiVersion\MultiVersion;
use MultiVersion\Protocol\ProtocolInterface;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\player\Player;

final class PlayerSession{

    private Player $player;
    private int $protocol;
    private MultiVersion $plugin;
    private ProtocolInterface $protocolInterface;
    private float $createdAt;
    private int $packetsSent = 0;
    private int $packetsReceived = 0;
    private array $metadata = [];

    public function __construct(Player $player, int $protocol, MultiVersion $plugin){
        $this->player = $player;
        $this->protocol = $protocol;
        $this->plugin = $plugin;
        $this->createdAt = microtime(true);

        $protocolInterface = $plugin->getVersionRegistry()->getProtocolInterface($protocol);
        if($protocolInterface === null){
            throw new \RuntimeException("Protocol {$protocol} not found");
        }

        $this->protocolInterface = $protocolInterface;
    }

    public function getPlayer(): Player{
        return $this->player;
    }

    public function getProtocol(): int{
        return $this->protocol;
    }

    public function getProtocolVersion(): string{
        return $this->protocolInterface->getMinecraftVersion();
    }

    public function getProtocolInterface(): ProtocolInterface{
        return $this->protocolInterface;
    }

    public function sendPacket(DataPacket $packet): void{
        if(!$this->player->isConnected()){
            return;
        }

        $session = $this->player->getNetworkSession();
        if($session === null){
            return;
        }

        try{
            $session->sendDataPacket($packet);
            $this->packetsSent++;
        }catch(\Throwable $e){
            $this->plugin->getMVLogger()->error(
                "Failed to send packet to {$this->player->getName()}: {$e->getMessage()}"
            );
        }
    }

    public function incrementPacketsReceived(): void{
        $this->packetsReceived++;
    }

    public function getPacketsSent(): int{
        return $this->packetsSent;
    }

    public function getPacketsReceived(): int{
        return $this->packetsReceived;
    }

    public function getCreatedAt(): float{
        return $this->createdAt;
    }

    public function getSessionDuration(): float{
        return microtime(true) - $this->createdAt;
    }

    public function setMetadata(string $key, mixed $value): void{
        $this->metadata[$key] = $value;
    }

    public function getMetadata(string $key): mixed{
        return $this->metadata[$key] ?? null;
    }

    public function hasMetadata(string $key): bool{
        return array_key_exists($key, $this->metadata);
    }

    public function removeMetadata(string $key): void{
        unset($this->metadata[$key]);
    }

    public function getAllMetadata(): array{
        return $this->metadata;
    }

    public function getStatistics(): array{
        return [
            'player' => $this->player->getName(),
            'protocol' => $this->protocol,
            'version' => $this->getProtocolVersion(),
            'packets_sent' => $this->packetsSent,
            'packets_received' => $this->packetsReceived,
            'session_duration' => $this->getSessionDuration(),
            'created_at' => date('Y-m-d H:i:s', (int)$this->createdAt)
        ];
    }
}
