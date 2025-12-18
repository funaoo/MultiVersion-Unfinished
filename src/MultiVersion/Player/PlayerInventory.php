<?php
declare(strict_types=1);

namespace MultiVersion\Player;

use MultiVersion\Item\Item;

class PlayerInventory {

    private Player $player;
    private array $slots = [];
    private int $selectedSlot = 0;
    private array $armor = [
        'helmet' => null,
        'chestplate' => null,
        'leggings' => null,
        'boots' => null
    ];
    private ?Item $offhand = null;
    private array $enderChest = [];
    private int $inventorySize = 36;
    private int $hotbarSize = 9;
    private int $enderChestSize = 27;
    private array $inventoryChanges = [];

    public function __construct(Player $player) {
        $this->player = $player;
        $this->initializeSlots();
    }

    private function initializeSlots(): void {
        for ($i = 0; $i < $this->inventorySize; $i++) {
            $this->slots[$i] = null;
        }

        for ($i = 0; $i < $this->enderChestSize; $i++) {
            $this->enderChest[$i] = null;
        }
    }

    public function getItem(int $slot): ?Item {
        if ($slot < 0 || $slot >= $this->inventorySize) {
            return null;
        }
        return $this->slots[$slot];
    }

    public function setItem(int $slot, ?Item $item): bool {
        if ($slot < 0 || $slot >= $this->inventorySize) {
            return false;
        }

        $oldItem = $this->slots[$slot];
        $this->slots[$slot] = $item;

        $this->recordChange($slot, $oldItem, $item);
        return true;
    }

    public function addItem(Item $item): bool {
        for ($i = 0; $i < $this->inventorySize; $i++) {
            if ($this->slots[$i] === null) {
                $this->setItem($i, $item);
                return true;
            }

            $slotItem = $this->slots[$i];
            if ($slotItem->canStackWith($item)) {
                $maxStack = 64;
                $available = $maxStack - $slotItem->getCount();

                if ($available > 0) {
                    $toAdd = min($available, $item->getCount());
                    $slotItem->setCount($slotItem->getCount() + $toAdd);
                    $item->setCount($item->getCount() - $toAdd);

                    if ($item->getCount() <= 0) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function removeItem(Item $item): bool {
        for ($i = 0; $i < $this->inventorySize; $i++) {
            $slotItem = $this->slots[$i];

            if ($slotItem !== null && $slotItem->equals($item)) {
                if ($slotItem->getCount() > $item->getCount()) {
                    $slotItem->setCount($slotItem->getCount() - $item->getCount());
                    return true;
                } elseif ($slotItem->getCount() === $item->getCount()) {
                    $this->setItem($i, null);
                    return true;
                } else {
                    $item->setCount($item->getCount() - $slotItem->getCount());
                    $this->setItem($i, null);
                }
            }
        }

        return $item->getCount() <= 0;
    }

    public function hasItem(Item $item): bool {
        $needed = $item->getCount();

        for ($i = 0; $i < $this->inventorySize; $i++) {
            $slotItem = $this->slots[$i];

            if ($slotItem !== null && $slotItem->equals($item)) {
                $needed -= $slotItem->getCount();

                if ($needed <= 0) {
                    return true;
                }
            }
        }

        return false;
    }

    public function clearSlot(int $slot): bool {
        return $this->setItem($slot, null);
    }

    public function clear(): void {
        for ($i = 0; $i < $this->inventorySize; $i++) {
            $this->slots[$i] = null;
        }
        $this->selectedSlot = 0;
    }

    public function getSelectedSlot(): int {
        return $this->selectedSlot;
    }

    public function setSelectedSlot(int $slot): bool {
        if ($slot < 0 || $slot >= $this->hotbarSize) {
            return false;
        }
        $this->selectedSlot = $slot;
        return true;
    }

    public function getItemInHand(): ?Item {
        return $this->getItem($this->selectedSlot);
    }

    public function setItemInHand(?Item $item): bool {
        return $this->setItem($this->selectedSlot, $item);
    }

    public function getArmorItem(string $slot): ?Item {
        return $this->armor[$slot] ?? null;
    }

    public function setArmorItem(string $slot, ?Item $item): bool {
        if (!isset($this->armor[$slot])) {
            return false;
        }
        $this->armor[$slot] = $item;
        return true;
    }

    public function getHelmet(): ?Item {
        return $this->armor['helmet'];
    }

    public function setHelmet(?Item $item): bool {
        return $this->setArmorItem('helmet', $item);
    }

    public function getChestplate(): ?Item {
        return $this->armor['chestplate'];
    }

    public function setChestplate(?Item $item): bool {
        return $this->setArmorItem('chestplate', $item);
    }

    public function getLeggings(): ?Item {
        return $this->armor['leggings'];
    }

    public function setLeggings(?Item $item): bool {
        return $this->setArmorItem('leggings', $item);
    }

    public function getBoots(): ?Item {
        return $this->armor['boots'];
    }

    public function setBoots(?Item $item): bool {
        return $this->setArmorItem('boots', $item);
    }

    public function getArmorContents(): array {
        return $this->armor;
    }

    public function clearArmor(): void {
        $this->armor = [
            'helmet' => null,
            'chestplate' => null,
            'leggings' => null,
            'boots' => null
        ];
    }

    public function getOffhand(): ?Item {
        return $this->offhand;
    }

    public function setOffhand(?Item $item): void {
        $this->offhand = $item;
    }

    public function getEnderChestItem(int $slot): ?Item {
        if ($slot < 0 || $slot >= $this->enderChestSize) {
            return null;
        }
        return $this->enderChest[$slot];
    }

    public function setEnderChestItem(int $slot, ?Item $item): bool {
        if ($slot < 0 || $slot >= $this->enderChestSize) {
            return false;
        }
        $this->enderChest[$slot] = $item;
        return true;
    }

    public function getEnderChestContents(): array {
        return $this->enderChest;
    }

    public function clearEnderChest(): void {
        for ($i = 0; $i < $this->enderChestSize; $i++) {
            $this->enderChest[$i] = null;
        }
    }

    public function getContents(): array {
        return $this->slots;
    }

    public function setContents(array $items): void {
        $this->clear();
        foreach ($items as $slot => $item) {
            if ($item instanceof Item) {
                $this->setItem($slot, $item);
            }
        }
    }

    public function getHotbarContents(): array {
        return array_slice($this->slots, 0, $this->hotbarSize, true);
    }

    public function getStorageContents(): array {
        return array_slice($this->slots, $this->hotbarSize, null, true);
    }

    public function getSize(): int {
        return $this->inventorySize;
    }

    public function getHotbarSize(): int {
        return $this->hotbarSize;
    }

    public function getEnderChestSize(): int {
        return $this->enderChestSize;
    }

    public function getEmptySlotCount(): int {
        $count = 0;
        foreach ($this->slots as $item) {
            if ($item === null) {
                $count++;
            }
        }
        return $count;
    }

    public function isFull(): bool {
        return $this->getEmptySlotCount() === 0;
    }

    public function isEmpty(): bool {
        foreach ($this->slots as $item) {
            if ($item !== null) {
                return false;
            }
        }
        return true;
    }

    public function getFirstEmptySlot(): int {
        for ($i = 0; $i < $this->inventorySize; $i++) {
            if ($this->slots[$i] === null) {
                return $i;
            }
        }
        return -1;
    }

    private function recordChange(int $slot, ?Item $oldItem, ?Item $newItem): void {
        $this->inventoryChanges[] = [
            'slot' => $slot,
            'old' => $oldItem,
            'new' => $newItem,
            'time' => microtime(true)
        ];

        if (count($this->inventoryChanges) > 100) {
            array_shift($this->inventoryChanges);
        }
    }

    public function getRecentChanges(): array {
        return $this->inventoryChanges;
    }

    public function clearChangeHistory(): void {
        $this->inventoryChanges = [];
    }

    public function save(): array {
        $slotsData = [];
        foreach ($this->slots as $slot => $item) {
            if ($item !== null) {
                $slotsData[$slot] = $item->jsonSerialize();
            }
        }

        $armorData = [];
        foreach ($this->armor as $slot => $item) {
            if ($item !== null) {
                $armorData[$slot] = $item->jsonSerialize();
            }
        }

        $enderChestData = [];
        foreach ($this->enderChest as $slot => $item) {
            if ($item !== null) {
                $enderChestData[$slot] = $item->jsonSerialize();
            }
        }

        return [
            'slots' => $slotsData,
            'selected_slot' => $this->selectedSlot,
            'armor' => $armorData,
            'offhand' => $this->offhand?->jsonSerialize(),
            'ender_chest' => $enderChestData
        ];
    }

    public function toArray(): array {
        return $this->save();
    }
}