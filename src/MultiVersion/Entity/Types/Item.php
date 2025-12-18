<?php
declare(strict_types=1);

namespace MultiVersion\Entity\Types;

use MultiVersion\Entity\Entity;

class Item extends Entity {

    protected int $pickupDelay = 10;
    protected int $despawnTimer = 6000;
    protected mixed $item = null;
    protected ?int $owner = null;
    protected ?int $thrower = null;

    protected function initEntity(): void {
        parent::initEntity();
        $this->width = 0.25;
        $this->height = 0.25;
        $this->hasGravity = true;
    }

    public function getNetworkTypeId(): int {
        return 64;
    }

    public function getItem(): mixed {
        return $this->item;
    }

    public function setItem(mixed $item): void {
        $this->item = $item;
    }

    public function getPickupDelay(): int {
        return $this->pickupDelay;
    }

    public function setPickupDelay(int $delay): void {
        $this->pickupDelay = max(0, $delay);
    }

    public function canBePickedUp(): bool {
        return $this->pickupDelay <= 0 && $this->isAlive;
    }

    public function getOwner(): ?int {
        return $this->owner;
    }

    public function setOwner(?int $entityId): void {
        $this->owner = $entityId;
    }

    public function getThrower(): ?int {
        return $this->thrower;
    }

    public function setThrower(?int $entityId): void {
        $this->thrower = $entityId;
    }

    public function tick(): void {
        if (!$this->isAlive) {
            return;
        }

        parent::tick();

        if ($this->pickupDelay > 0) {
            $this->pickupDelay--;
        }

        $this->despawnTimer--;
        if ($this->despawnTimer <= 0) {
            $this->kill();
        }

        if ($this->isColliding) {
            $this->tryMergeWithNearby();
        }
    }

    private function tryMergeWithNearby(): void {
    }

    public function saveToArray(): array {
        $data = parent::saveToArray();
        $data['item'] = $this->item;
        $data['pickupDelay'] = $this->pickupDelay;
        $data['despawnTimer'] = $this->despawnTimer;
        $data['owner'] = $this->owner;
        $data['thrower'] = $this->thrower;
        return $data;
    }

    public function loadFromArray(array $data): void {
        parent::loadFromArray($data);
        $this->item = $data['item'] ?? null;
        $this->pickupDelay = $data['pickupDelay'] ?? 10;
        $this->despawnTimer = $data['despawnTimer'] ?? 6000;
        $this->owner = $data['owner'] ?? null;
        $this->thrower = $data['thrower'] ?? null;
    }
}