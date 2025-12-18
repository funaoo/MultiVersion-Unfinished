<?php

declare(strict_types=1);

namespace MultiVersion\Handler;

use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\PlayerHotbarPacket;
use pocketmine\network\mcpe\protocol\ItemStackRequestPacket;
use pocketmine\network\mcpe\protocol\ItemStackResponsePacket;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use MultiVersion\Network\PlayerSession;
use MultiVersion\Network\PacketRegistry;

final class InventoryHandler extends PacketHandler {

    private array $openContainers = [];
    private array $transactionQueue = [];
    private array $itemTranslationCache = [];
    private array $craftingRecipes = [];

    protected function initialize(): void {
        $this->handledPackets = [
            InventoryTransactionPacket::class,
            MobEquipmentPacket::class,
            ContainerOpenPacket::class,
            ContainerClosePacket::class,
            InventoryContentPacket::class,
            InventorySlotPacket::class,
            PlayerHotbarPacket::class,
            ItemStackRequestPacket::class,
            ItemStackResponsePacket::class,
            CraftingDataPacket::class
        ];
    }

    public function register(PacketRegistry $registry): void {
        $this->registerPacket($registry, InventoryTransactionPacket::class,
            fn($p, $s) => $this->handleInventoryTransaction($p, $s), 10);

        $this->registerPacket($registry, MobEquipmentPacket::class,
            fn($p, $s) => $this->handleMobEquipment($p, $s), 10);

        $this->registerPacket($registry, ContainerOpenPacket::class,
            fn($p, $s) => $this->handleContainerOpen($p, $s), 10);

        $this->registerPacket($registry, ContainerClosePacket::class,
            fn($p, $s) => $this->handleContainerClose($p, $s), 10);

        $this->registerPacket($registry, PlayerHotbarPacket::class,
            fn($p, $s) => $this->handlePlayerHotbar($p, $s), 5);

        $this->registerPacket($registry, ItemStackRequestPacket::class,
            fn($p, $s) => $this->handleItemStackRequest($p, $s), 10);
    }

    private function handleInventoryTransaction(InventoryTransactionPacket $packet, PlayerSession $session): void {
        $player = $session->getPlayer();
        $trx = $packet->trx;

        $this->transactionQueue[$player->getName()][] = [
            'transaction' => $trx,
            'time' => microtime(true)
        ];

        if (count($this->transactionQueue[$player->getName()]) > 50) {
            array_shift($this->transactionQueue[$player->getName()]);
        }
    }

    private function handleMobEquipment(MobEquipmentPacket $packet, PlayerSession $session): void {
        $packet->item = $this->translateItem($packet->item, $session);
    }

    private function handleContainerOpen(ContainerOpenPacket $packet, PlayerSession $session): void {
        $this->openContainers[$session->getPlayer()->getName()] = [
            'window' => $packet->windowId,
            'type' => $packet->type,
            'time' => microtime(true)
        ];
    }

    private function handleContainerClose(ContainerClosePacket $packet, PlayerSession $session): void {
        unset($this->openContainers[$session->getPlayer()->getName()]);
    }

    private function handlePlayerHotbar(PlayerHotbarPacket $packet, PlayerSession $session): void {}

    private function handleItemStackRequest(ItemStackRequestPacket $packet, PlayerSession $session): void {
        $responses = [];

        foreach ($packet->getRequests() as $request) {
            $responses[] = [
                'requestId' => $request->getRequestId(),
                'success' => true
            ];
        }

        $pk = new ItemStackResponsePacket();
        $pk->responses = $responses;
        $session->sendPacket($pk);
    }

    private function translateItem(object $item, PlayerSession $session): object {
        $protocol = $session->getProtocol();
        $id = $item->getId();
        $key = $id . ':' . $protocol;

        if (isset($this->itemTranslationCache[$key])) {
            return $this->itemTranslationCache[$key];
        }

        $this->itemTranslationCache[$key] = $item;
        return $item;
    }

    public function sendInventoryContent(PlayerSession $session, int $windowId, array $items): void {
        $pk = new InventoryContentPacket();
        $pk->windowId = $windowId;
        $pk->items = $items;
        $session->sendPacket($pk);
    }

    public function sendInventorySlot(PlayerSession $session, int $windowId, int $slot, object $item): void {
        $pk = new InventorySlotPacket();
        $pk->windowId = $windowId;
        $pk->inventorySlot = $slot;
        $pk->item = $item;
        $session->sendPacket($pk);
    }

    public function sendCraftingData(PlayerSession $session): void {
        $pk = new CraftingDataPacket();
        $pk->entries = $this->craftingRecipes[$session->getProtocol()] ?? [];
        $pk->cleanRecipes = true;
        $session->sendPacket($pk);
    }

    public function clearItemTranslationCache(): void {
        $this->itemTranslationCache = [];
    }

    public function clearPlayerData(string $playerName): void {
        unset($this->openContainers[$playerName]);
        unset($this->transactionQueue[$playerName]);
    }

    public function getTransactionQueue(string $playerName): array {
        return $this->transactionQueue[$playerName] ?? [];
    }

    public function getCacheStatistics(): array {
        return [
            'cache' => count($this->itemTranslationCache),
            'containers' => count($this->openContainers),
            'transactions' => array_sum(array_map('count', $this->transactionQueue))
        ];
    }
}
