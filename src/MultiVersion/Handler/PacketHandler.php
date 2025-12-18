<?php

declare(strict_types=1);

namespace MultiVersion\Handler;

use MultiVersion\MultiVersion;
use MultiVersion\Network\PacketRegistry;
use MultiVersion\Network\PlayerSession;
use pocketmine\network\mcpe\protocol\DataPacket;

abstract class PacketHandler {

    protected MultiVersion $plugin;
    protected array $handledPackets = [];

    public function __construct(MultiVersion $plugin) {
        $this->plugin = $plugin;
        $this->initialize();
    }

    abstract protected function initialize(): void;

    abstract public function register(PacketRegistry $registry): void;

    protected function registerPacket(PacketRegistry $registry, string $packetClass, callable $handler, int $priority = 0): void {
        $this->handledPackets[] = $packetClass;
        $registry->registerHandler($packetClass, function(DataPacket $packet, PlayerSession $session) use ($handler) {
            try {
                $handler($packet, $session);
            } catch (\Exception $e) {
                $this->handleError($e, $packet, $session);
            }
        }, $priority);
    }

    protected function handleError(\Exception $e, DataPacket $packet, PlayerSession $session): void {
        $this->plugin->getMVLogger()->error(
            "Error handling packet " . get_class($packet) .
            " for player " . $session->getPlayer()->getName() .
            ": " . $e->getMessage()
        );

        $this->plugin->getServerManager()->incrementTranslationErrors();
    }

    protected function translatePacket(DataPacket $packet, PlayerSession $session): ?DataPacket {
        $playerProtocol = $session->getProtocol();
        $serverProtocol = $this->plugin->getServerManager()->getServerProtocol();

        if ($playerProtocol === $serverProtocol) {
            return $packet;
        }

        $translator = $this->plugin->getPacketRouter();
        return $translator->route($packet, $session->getPlayer()->getName()) ? $packet : null;
    }

    public function getHandledPackets(): array {
        return $this->handledPackets;
    }

    protected function logPacket(string $message, PlayerSession $session, string $level = 'debug'): void {
        if ($this->plugin->getMVConfig()->shouldLogPackets()) {
            $this->plugin->getMVLogger()->{$level}(
                "[{$session->getPlayer()->getName()}] {$message}"
            );
        }
    }
}