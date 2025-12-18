<?php
declare(strict_types=1);

namespace MultiVersion\Item;

use pocketmine\item\Item as PMItem;
use pocketmine\nbt\tag\CompoundTag;

class Item {

    private int $id;
    private int $meta;
    private int $count;
    private ?CompoundTag $nbt;
    private string $name;
    private array $enchantments = [];
    private array $customData = [];

    public function __construct(int $id, int $meta = 0, int $count = 1, ?CompoundTag $nbt = null) {
        $this->id = $id;
        $this->meta = $meta;
        $this->count = $count;
        $this->nbt = $nbt;
        $this->name = "";
    }

    public static function fromPocketMineItem(PMItem $item): self {
        $nbt = null;
        if ($item->hasNamedTag()) {
            $nbt = clone $item->getNamedTag();
        }

        $multiItem = new self(
            $item->getId(),
            $item->getMeta(),
            $item->getCount(),
            $nbt
        );

        $multiItem->setName($item->hasCustomName() ? $item->getCustomName() : "");

        return $multiItem;
    }

    public function toPocketMineItem(): PMItem {
        $item = PMItem::get($this->id, $this->meta, $this->count);

        if ($this->nbt !== null) {
            $item->setNamedTag(clone $this->nbt);
        }

        if (!empty($this->name)) {
            $item->setCustomName($this->name);
        }

        return $item;
    }

    public function getId(): int {
        return $this->id;
    }

    public function setId(int $id): void {
        $this->id = $id;
    }

    public function getMeta(): int {
        return $this->meta;
    }

    public function setMeta(int $meta): void {
        $this->meta = $meta;
    }

    public function getCount(): int {
        return $this->count;
    }

    public function setCount(int $count): void {
        $this->count = max(0, min(255, $count));
    }

    public function getNbt(): ?CompoundTag {
        return $this->nbt;
    }

    public function setNbt(?CompoundTag $nbt): void {
        $this->nbt = $nbt;
    }

    public function hasNbt(): bool {
        return $this->nbt !== null;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function hasCustomName(): bool {
        return !empty($this->name);
    }

    public function addEnchantment(int $id, int $level): void {
        $this->enchantments[$id] = $level;
    }

    public function removeEnchantment(int $id): void {
        unset($this->enchantments[$id]);
    }

    public function getEnchantments(): array {
        return $this->enchantments;
    }

    public function hasEnchantment(int $id): bool {
        return isset($this->enchantments[$id]);
    }

    public function getEnchantmentLevel(int $id): int {
        return $this->enchantments[$id] ?? 0;
    }

    public function setCustomData(string $key, mixed $value): void {
        $this->customData[$key] = $value;
    }

    public function getCustomData(string $key): mixed {
        return $this->customData[$key] ?? null;
    }

    public function hasCustomData(string $key): bool {
        return isset($this->customData[$key]);
    }

    public function removeCustomData(string $key): void {
        unset($this->customData[$key]);
    }

    public function getAllCustomData(): array {
        return $this->customData;
    }

    public function isNull(): bool {
        return $this->id === 0 || $this->count <= 0;
    }

    public function equals(Item $other): bool {
        return $this->id === $other->id &&
            $this->meta === $other->meta &&
            $this->equalsExact($other);
    }

    public function equalsExact(Item $other): bool {
        if ($this->nbt !== null && $other->nbt !== null) {
            return $this->nbt->equals($other->nbt);
        }

        return $this->nbt === null && $other->nbt === null;
    }

    public function canStackWith(Item $other): bool {
        return $this->equals($other) && !$this->hasCustomName() && !$other->hasCustomName();
    }

    public function clone(): self {
        $cloned = new self($this->id, $this->meta, $this->count, $this->nbt !== null ? clone $this->nbt : null);
        $cloned->name = $this->name;
        $cloned->enchantments = $this->enchantments;
        $cloned->customData = $this->customData;
        return $cloned;
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'meta' => $this->meta,
            'count' => $this->count,
            'name' => $this->name,
            'enchantments' => $this->enchantments,
            'has_nbt' => $this->nbt !== null
        ];
    }

    public function __toString(): string {
        return "Item(id={$this->id}, meta={$this->meta}, count={$this->count})";
    }
}