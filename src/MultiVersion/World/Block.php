<?php
declare(strict_types=1);

namespace MultiVersion\World;

final class Block {

    private int $id;
    private int $meta;
    private int $runtimeId;
    private array $properties = [];
    private ?string $name = null;

    public function __construct(int $id, int $meta = 0, int $runtimeId = 0) {
        $this->id = $id;
        $this->meta = $meta;
        $this->runtimeId = $runtimeId;
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
        $this->meta = $meta & 0x0f;
    }

    public function getRuntimeId(): int {
        return $this->runtimeId;
    }

    public function setRuntimeId(int $runtimeId): void {
        $this->runtimeId = $runtimeId;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function getProperty(string $key): mixed {
        return $this->properties[$key] ?? null;
    }

    public function setProperty(string $key, mixed $value): void {
        $this->properties[$key] = $value;
    }

    public function hasProperty(string $key): bool {
        return isset($this->properties[$key]);
    }

    public function removeProperty(string $key): void {
        unset($this->properties[$key]);
    }

    public function getProperties(): array {
        return $this->properties;
    }

    public function setProperties(array $properties): void {
        $this->properties = $properties;
    }

    public function isAir(): bool {
        return $this->id === 0;
    }

    public function isSolid(): bool {
        return !$this->isAir() && !$this->isLiquid();
    }

    public function isLiquid(): bool {
        return in_array($this->id, [8, 9, 10, 11], true);
    }

    public function isTransparent(): bool {
        return $this->isAir() || in_array($this->id, [8, 9, 10, 11, 20, 95], true);
    }

    public function isReplaceable(): bool {
        return $this->isAir() || $this->isLiquid() || in_array($this->id, [6, 31, 32, 37, 38, 39, 40], true);
    }

    public function getFullId(): int {
        return ($this->id << 4) | $this->meta;
    }

    public function equals(Block $other): bool {
        return $this->id === $other->id && $this->meta === $other->meta;
    }

    public function isSameType(Block $other): bool {
        return $this->id === $other->id;
    }

    public function clone(): self {
        $cloned = new self($this->id, $this->meta, $this->runtimeId);
        $cloned->name = $this->name;
        $cloned->properties = $this->properties;
        return $cloned;
    }

    public function __toString(): string {
        return "Block(id={$this->id}, meta={$this->meta}, runtime={$this->runtimeId})";
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'meta' => $this->meta,
            'runtimeId' => $this->runtimeId,
            'name' => $this->name,
            'properties' => $this->properties
        ];
    }

    public static function fromFullId(int $fullId): self {
        $id = $fullId >> 4;
        $meta = $fullId & 0x0f;
        return new self($id, $meta);
    }

    public static function air(): self {
        return new self(0, 0, 0);
    }

    public static function stone(): self {
        return new self(1, 0);
    }

    public static function grass(): self {
        return new self(2, 0);
    }

    public static function dirt(): self {
        return new self(3, 0);
    }

    public static function bedrock(): self {
        return new self(7, 0);
    }
}