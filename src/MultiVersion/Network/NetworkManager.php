<?php
declare(strict_types=1);

namespace MultiVersion\Network;

use MultiVersion\MultiVersion;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\player\Player;

final class NetworkManager implements Listener{

    private MultiVersion $plugin;
    private PacketRegistry $packetRegistry;
    private VersionDetector $versionDetector;
    private array $sessions = [];

    public function __construct(MultiVersion $plugin){
        $this->plugin = $plugin;
        $this->packetRegistry = $plugin->getPacketRegistry();
        $this->versionDetector = new VersionDetector($plugin);
    }

    public function onPlayerJoin(PlayerJoinEvent $event): void{
        $this->createSession($event->getPlayer());
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void{
        $this->removeSession($event->getPlayer());
    }

    public function onDataPacketReceive(DataPacketReceiveEvent $event): void{
        $player = $event->getOrigin()->getPlayer();
        if($player === null){
            return;
        }

        $session = $this->getSession($player);
        if($session === null){
            return;
        }

        $this->packetRegistry->handleIncoming($event->getPacket(), $session);
    }

    public function onDataPacketSend(DataPacketSendEvent $event): void{
        foreach($event->getTargets() as $target){
            $player = $target->getPlayer();
            if($player === null){
                continue;
            }

            $session = $this->getSession($player);
            if($session === null){
                continue;
            }

            foreach($event->getPackets() as $packet){
                $this->packetRegistry->handleOutgoing($packet, $session);
            }
        }
    }

    private function createSession(Player $player): void{
        $protocol = $this->versionDetector->detect($player);
        $session = new PlayerSession($player, $protocol, $this->plugin);

        $this->sessions[$player->getName()] = $session;
        $this->plugin->getVersionRegistry()->register($player->getName(), $protocol);

        $this->plugin->getMVLogger()->info(
            "Session created for {$player->getName()} (protocol {$protocol})"
        );
    }

    private function removeSession(Player $player): void{
        $name = $player->getName();

        if(!isset($this->sessions[$name])){
            return;
        }

        $protocol = $this->sessions[$name]->getProtocol();

        $this->plugin->getVersionRegistry()->unregister($name);
        $this->plugin->getChunkHandler()->clearPlayerData($name);
        $this->plugin->getGameHandler()->clearPlayerData($name);
        $this->plugin->getInventoryHandler()->clearPlayerData($name);
        $this->plugin->getCommandHandler()->clearPlayerData($name);

        unset($this->sessions[$name]);

        $this->plugin->getMVLogger()->info(
            "Session removed for {$name} (protocol {$protocol})"
        );
    }

    public function getSession(Player $player): ?PlayerSession{
        return $this->sessions[$player->getName()] ?? null;
    }

    public function hasSession(string $playerName): bool{
        return isset($this->sessions[$playerName]);
    }

    public function getAllSessions(): array{
        return $this->sessions;
    }

    public function getActiveSessionCount(): int{
        return count($this->sessions);
    }

    public function getSessionsByProtocol(int $protocol): array{
        return array_filter(
            $this->sessions,
            fn(PlayerSession $session) => $session->getProtocol() === $protocol
        );
    }
}
