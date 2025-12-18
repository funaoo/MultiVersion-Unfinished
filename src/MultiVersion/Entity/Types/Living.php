<?php
declare(strict_types=1);

namespace MultiVersion\Entity\Types;

use MultiVersion\Entity\Entity;

abstract class Living extends Entity {

    protected float $health = 20.0;
    protected float $maxHealth = 20.0;
    protected int $airTicks = 300;
    protected int $maxAirTicks = 300;
    protected bool $isBreathing = true;
    protected array $effects = [];
    protected float $attackDamage = 1.0;
    protected float $movementSpeed = 0.1;
    protected int $hurtTime = 0;
    protected int $deathTime = 0;

    protected function initEntity(): void {
        parent::initEntity();
        $this->setDataProperty(7, $this->health);
        $this->setDataProperty(1, $this->airTicks);
    }

    public function getHealth(): float {
        return $this->health;
    }

    public function setHealth(float $health): void {
        $this->health = max(0, min($this->maxHealth, $health));
        $this->setDataProperty(7, (int)$this->health);

        if ($this->health <= 0) {
            $this->kill();
        }
    }

    public function getMaxHealth(): float {
        return $this->maxHealth;
    }

    public function setMaxHealth(float $maxHealth): void {
        $this->maxHealth = max(1, $maxHealth);
        if ($this->health > $this->maxHealth) {
            $this->health = $this->maxHealth;
        }
    }

    public function heal(float $amount): void {
        $this->setHealth($this->health + $amount);
    }

    public function damage(float $amount): void {
        if ($amount <= 0) {
            return;
        }

        $this->setHealth($this->health - $amount);
        $this->hurtTime = 10;
    }

    public function isAlive(): bool {
        return parent::isAlive() && $this->health > 0;
    }

    public function kill(): void {
        parent::kill();
        $this->health = 0;
        $this->deathTime = 0;
    }

    public function getAirTicks(): int {
        return $this->airTicks;
    }

    public function setAirTicks(int $ticks): void {
        $this->airTicks = max(0, min($this->maxAirTicks, $ticks));
        $this->setDataProperty(1, $this->airTicks);
    }

    public function getMaxAirTicks(): int {
        return $this->maxAirTicks;
    }

    public function isBreathing(): bool {
        return $this->isBreathing;
    }

    public function setBreathing(bool $breathing): void {
        $this->isBreathing = $breathing;
    }

    public function addEffect(int $effectId, int $duration, int $amplifier = 0): void {
        $this->effects[$effectId] = [
            'duration' => $duration,
            'amplifier' => $amplifier,
            'startTime' => $this->age
        ];
    }

    public function removeEffect(int $effectId): void {
        unset($this->effects[$effectId]);
    }

    public function hasEffect(int $effectId): bool {
        return isset($this->effects[$effectId]);
    }

    public function getEffect(int $effectId): ?array {
        return $this->effects[$effectId] ?? null;
    }

    public function getAllEffects(): array {
        return $this->effects;
    }

    public function clearEffects(): void {
        $this->effects = [];
    }

    public function getAttackDamage(): float {
        return $this->attackDamage;
    }

    public function setAttackDamage(float $damage): void {
        $this->attackDamage = max(0, $damage);
    }

    public function getMovementSpeed(): float {
        return $this->movementSpeed;
    }

    public function setMovementSpeed(float $speed): void {
        $this->movementSpeed = max(0, $speed);
    }

    public function isHurt(): bool {
        return $this->hurtTime > 0;
    }

    public function getHurtTime(): int {
        return $this->hurtTime;
    }

    public function tick(): void {
        if (!$this->isAlive) {
            $this->deathTime++;
            return;
        }

        parent::tick();

        if ($this->hurtTime > 0) {
            $this->hurtTime--;
        }

        $this->tickAir();
        $this->tickEffects();
    }

    protected function tickAir(): void {
        if ($this->world === null) {
            return;
        }

        $block = $this->world->getBlock((int)floor($this->x), (int)floor($this->y + $this->height), (int)floor($this->z));

        if ($block->isLiquid()) {
            $this->isBreathing = false;
            $this->setAirTicks($this->airTicks - 1);

            if ($this->airTicks <= -20) {
                $this->damage(2.0);
                $this->setAirTicks(0);
            }
        } else {
            $this->isBreathing = true;
            if ($this->airTicks < $this->maxAirTicks) {
                $this->setAirTicks($this->airTicks + 5);
            }
        }
    }

    protected function tickEffects(): void {
        foreach ($this->effects as $effectId => $effect) {
            $elapsed = $this->age - $effect['startTime'];

            if ($elapsed >= $effect['duration']) {
                $this->removeEffect($effectId);
                continue;
            }

            $this->applyEffectTick($effectId, $effect['amplifier']);
        }
    }

    protected function applyEffectTick(int $effectId, int $amplifier): void {
        switch ($effectId) {
            case 6:
                $this->heal(1.0 * ($amplifier + 1));
                break;
            case 19:
                $this->damage(1.0 * ($amplifier + 1));
                break;
        }
    }

    public function attack(Living $target, float $damage): void {
        $target->damage($damage);
    }

    public function saveToArray(): array {
        $data = parent::saveToArray();
        $data['health'] = $this->health;
        $data['maxHealth'] = $this->maxHealth;
        $data['airTicks'] = $this->airTicks;
        $data['effects'] = $this->effects;
        $data['attackDamage'] = $this->attackDamage;
        $data['movementSpeed'] = $this->movementSpeed;
        return $data;
    }

    public function loadFromArray(array $data): void {
        parent::loadFromArray($data);
        $this->health = $data['health'] ?? 20.0;
        $this->maxHealth = $data['maxHealth'] ?? 20.0;
        $this->airTicks = $data['airTicks'] ?? 300;
        $this->effects = $data['effects'] ?? [];
        $this->attackDamage = $data['attackDamage'] ?? 1.0;
        $this->movementSpeed = $data['movementSpeed'] ?? 0.1;
    }
}