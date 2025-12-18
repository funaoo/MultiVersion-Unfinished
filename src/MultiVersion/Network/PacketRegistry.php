<?php
declare(strict_types=1);

namespace MultiVersion\Network;

use MultiVersion\MultiVersion;
use pocketmine\network\mcpe\protocol\DataPacket;

final class PacketRegistry{

    private MultiVersion $plugin;
    private array $incomingHandlers = [];
    private array $outgoingHandlers = [];
    private array $incomingPriorities = [];
    private array $outgoingPriorities = [];

    public function __construct(MultiVersion $plugin){
        $this->plugin = $plugin;
    }

    public function registerHandler(string $packetClass, callable $handler, int $priority = 0): void{
        $this->incomingHandlers[$packetClass][] = $handler;
        $this->incomingPriorities[$packetClass][] = $priority;
        $this->sortIncoming($packetClass);
    }

    public function registerOutgoingHandler(string $packetClass, callable $handler, int $priority = 0): void{
        $this->outgoingHandlers[$packetClass][] = $handler;
        $this->outgoingPriorities[$packetClass][] = $priority;
        $this->sortOutgoing($packetClass);
    }

    private function sortIncoming(string $packetClass): void{
        if(!isset($this->incomingHandlers[$packetClass])){
            return;
        }

        array_multisort(
            $this->incomingPriorities[$packetClass],
            SORT_DESC,
            $this->incomingHandlers[$packetClass]
        );
    }

    private function sortOutgoing(string $packetClass): void{
        if(!isset($this->outgoingHandlers[$packetClass])){
            return;
        }

        array_multisort(
            $this->outgoingPriorities[$packetClass],
            SORT_DESC,
            $this->outgoingHandlers[$packetClass]
        );
    }

    public function handleIncoming(DataPacket $packet, PlayerSession $session): void{
        $session->incrementPacketsReceived();

        $packetClass = $packet::class;

        if(!isset($this->incomingHandlers[$packetClass])){
            return;
        }

        foreach($this->incomingHandlers[$packetClass] as $handler){
            try{
                $handler($packet, $session);
            }catch(\Throwable $e){
                $this->plugin->getMVLogger()->error(
                    "Incoming handler error ({$packetClass}): {$e->getMessage()}"
                );
            }
        }
    }

    public function handleOutgoing(DataPacket $packet, PlayerSession $session): void{
        $packetClass = $packet::class;

        if(!isset($this->outgoingHandlers[$packetClass])){
            return;
        }

        foreach($this->outgoingHandlers[$packetClass] as $handler){
            try{
                $handler($packet, $session);
            }catch(\Throwable $e){
                $this->plugin->getMVLogger()->error(
                    "Outgoing handler error ({$packetClass}): {$e->getMessage()}"
                );
            }
        }
    }

    public function unregisterHandler(string $packetClass): void{
        unset(
            $this->incomingHandlers[$packetClass],
            $this->incomingPriorities[$packetClass],
            $this->outgoingHandlers[$packetClass],
            $this->outgoingPriorities[$packetClass]
        );
    }

    public function hasHandler(string $packetClass): bool{
        return isset($this->incomingHandlers[$packetClass]) || isset($this->outgoingHandlers[$packetClass]);
    }

    public function getHandlerCount(string $packetClass): int{
        return count($this->incomingHandlers[$packetClass] ?? [])
            + count($this->outgoingHandlers[$packetClass] ?? []);
    }

    public function getAllRegisteredPackets(): array{
        return array_values(array_unique(array_merge(
            array_keys($this->incomingHandlers),
            array_keys($this->outgoingHandlers)
        )));
    }

    public function getStatistics(): array{
        return [
            "packet_types" => count($this->getAllRegisteredPackets()),
            "incoming_handlers" => array_sum(array_map("count", $this->incomingHandlers)),
            "outgoing_handlers" => array_sum(array_map("count", $this->outgoingHandlers))
        ];
    }
}
