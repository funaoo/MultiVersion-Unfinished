<?php
declare(strict_types=1);

namespace MultiVersion\Item;

use MultiVersion\MultiVersion;
use pocketmine\item\Item as PMItem;
use pocketmine\nbt\tag\CompoundTag;

final class ItemFactory {

    private static ?ItemFactory $instance = null;
    private MultiVersion $plugin;
    private ItemRegistry $registry;

    public function __construct(MultiVersion $plugin) {
        $this->plugin = $plugin;
        $this->registry = new ItemRegistry($plugin);
        self::$instance = $this;
    }

    public static function getInstance(): ?ItemFactory {
        return self::$instance;
    }

    public function createItem(int $id, int $meta = 0, int $count = 1): Item {
        if (!$this->registry->isItemRegistered($id)) {
            $this->plugin->getMVLogger()->warning("Creating unregistered item: {$id}");
        }

        return new Item($id, $meta, $count);
    }

    public function createItemFromString(string $itemString): ?Item {
        $parts = explode(':', $itemString);

        if (count($parts) < 1) {
            return null;
        }

        $id = $this->parseItemId($parts[0]);
        if ($id === null) {
            return null;
        }

        $meta = isset($parts[1]) ? (int)$parts[1] : 0;
        $count = isset($parts[2]) ? (int)$parts[2] : 1;

        return $this->createItem($id, $meta, $count);
    }

    private function parseItemId(string $input): ?int {
        if (is_numeric($input)) {
            return (int)$input;
        }

        return $this->registry->getItemByName($input);
    }

    public function createItemFromNbt(CompoundTag $nbt): Item {
        $id = $nbt->getShort('id', 0);
        $meta = $nbt->getShort('Damage', 0);
        $count = $nbt->getByte('Count', 1);

        $item = new Item($id, $meta, $count);

        if ($nbt->hasTag('tag')) {
            $tag = $nbt->getCompoundTag('tag');
            if ($tag !== null) {
                $item->setNbt(clone $tag);
            }
        }

        if ($nbt->hasTag('display')) {
            $display = $nbt->getCompoundTag('display');
            if ($display !== null && $display->hasTag('Name')) {
                $item->setName($display->getString('Name'));
            }
        }

        return $item;
    }

    public function itemToNbt(Item $item): CompoundTag {
        $nbt = new CompoundTag();
        $nbt->setShort('id', $item->getId());
        $nbt->setShort('Damage', $item->getMeta());
        $nbt->setByte('Count', $item->getCount());

        if ($item->hasNbt()) {
            $nbt->setTag('tag', clone $item->getNbt());
        }

        if ($item->hasCustomName()) {
            $display = new CompoundTag();
            $display->setString('Name', $item->getName());
            $nbt->setTag('display', $display);
        }

        return $nbt;
    }

    public function fromPocketMineItem(PMItem $pmItem): Item {
        return Item::fromPocketMineItem($pmItem);
    }

    public function toPocketMineItem(Item $item): PMItem {
        return $item->toPocketMineItem();
    }

    public function createAir(): Item {
        return new Item(0, 0, 0);
    }

    public function createStone(int $count = 1): Item {
        return $this->createItem(1, 0, $count);
    }

    public function createDiamond(int $count = 1): Item {
        return $this->createItem(264, 0, $count);
    }

    public function createDiamondSword(): Item {
        return $this->createItem(276, 0, 1);
    }

    public function createDiamondPickaxe(): Item {
        return $this->createItem(278, 0, 1);
    }

    public function cloneItem(Item $item): Item {
        return $item->clone();
    }

    public function mergeItems(Item $item1, Item $item2): ?Item {
        if (!$item1->canStackWith($item2)) {
            return null;
        }

        $maxStack = $this->registry->getMaxStackSize($item1->getId());
        $totalCount = $item1->getCount() + $item2->getCount();

        if ($totalCount > $maxStack) {
            return null;
        }

        $merged = $item1->clone();
        $merged->setCount($totalCount);

        return $merged;
    }

    public function splitItem(Item $item, int $amount): ?array {
        if ($amount <= 0 || $amount >= $item->getCount()) {
            return null;
        }

        $item1 = $item->clone();
        $item1->setCount($amount);

        $item2 = $item->clone();
        $item2->setCount($item->getCount() - $amount);

        return [$item1, $item2];
    }

    public function getRegistry(): ItemRegistry {
        return $this->registry;
    }
}