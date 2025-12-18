<?php

declare(strict_types=1);

namespace MultiVersion\Events;

final class PacketSendEvent extends Event {

    private \pocketmine\network\mcpe\protocol\DataPacket $packet;
    private \pocketmine\player\Player $player;
    private int $protocol;
    private string $packetName;
    private int $packetId;
    private bool $immediate;

    public function __construct(\pocketmine\network\mcpe\protocol\DataPacket $packet, \pocketmine\player\Player $player, int $protocol, bool $immediate = false) {
        parent::__construct();
        $this->packet = $packet;
        $this->player = $player;
        $this->protocol = $protocol;
        $this->immediate = $immediate;
        $this->packetName = $this->getPacketName();
        $this->packetId = $this->getPacketId();
    }

    private function getPacketName(): string {
        $className = get_class($this->packet);
        $parts = explode('\\', $className);
        return end($parts);
    }

    private function getPacketId(): int {
        if (method_exists($this->packet, 'pid')) {
            return $this->packet->pid();
        }

        return 0;
    }

    public function getPacket(): \pocketmine\network\mcpe\protocol\DataPacket {
        return $this->packet;
    }

    public function getPlayer(): \pocketmine\player\Player {
        return $this->player;
    }

    public function getProtocol(): int {
        return $this->protocol;
    }

    public function getPacketName(): string {
        return $this->packetName;
    }

    public function getPacketIdValue(): int {
        return $this->packetId;
    }

    public function isImmediate(): bool {
        return $this->immediate;
    }

    public function setPacket(\pocketmine\network\mcpe\protocol\DataPacket $packet): void {
        $this->packet = $packet;
    }

    public function setImmediate(bool $immediate): void {
        $this->immediate = $immediate;
    }
}