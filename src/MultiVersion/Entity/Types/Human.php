<?php
declare(strict_types=1);

namespace MultiVersion\Entity\Types;

abstract class Human extends Living {

    protected string $username = "";
    protected string $uuid = "";
    protected string $xuid = "";
    protected array $inventory = [];
    protected int $selectedSlot = 0;
    protected int $gameMode = 0;
    protected array $armor = [];
    protected float $hunger = 20.0;
    protected float $saturation = 5.0;
    protected float $exhaustion = 0.0;
    protected int $experience = 0;
    protected int $experienceLevel = 0;
    protected array $permissions = [];
    protected bool $isOp = false;
    protected array $abilities = [];

    protected function initEntity(): void {
        parent::initEntity();
        $this->maxHealth = 20.0;
        $this->health = 20.0;
        $this->width = 0.6;
        $this->height = 1.8;

        $this->initializeInventory();
        $this->initializeArmor();
        $this->initializeAbilities();
    }

    private function initializeInventory(): void {
        for ($i = 0; $i < 36; $i++) {
            $this->inventory[$i] = null;
        }
    }

    private function initializeArmor(): void {
        $this->armor = [
            'helmet' => null,
            'chestplate' => null,
            'leggings' => null,
            'boots' => null
        ];
    }

    private function initializeAbilities(): void {
        $this->abilities = [
            'fly' => false,
            'flying' => false,
            'walkSpeed' => 0.1,
            'flySpeed' => 0.05,
            'mayfly' => false,
            'instabuild' => false,
            'invulnerable' => false,
            'muted' => false,
            'worldbuilder' => false,
            'noclip' => false
        ];
    }

    public function getNetworkTypeId(): int {
        return 63;
    }

    public function getUsername(): string {
        return $this->username;
    }

    public function setUsername(string $username): void {
        $this->username = $username;
        if (empty($this->nameTag)) {
            $this->setNameTag($username);
        }
    }

    public function getUuid(): string {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void {
        $this->uuid = $uuid;
    }

    public function getXuid(): string {
        return $this->xuid;
    }

    public function setXuid(string $xuid): void {
        $this->xuid = $xuid;
    }

    public function getInventory(): array {
        return $this->inventory;
    }

    public function getInventoryItem(int $slot): mixed {
        if ($slot < 0 || $slot >= 36) {
            return null;
        }
        return $this->inventory[$slot] ?? null;
    }

    public function setInventoryItem(int $slot, mixed $item): void {
        if ($slot < 0 || $slot >= 36) {
            return;
        }
        $this->inventory[$slot] = $item;
    }

    public function clearInventorySlot(int $slot): void {
        if ($slot >= 0 && $slot < 36) {
            $this->inventory[$slot] = null;
        }
    }

    public function clearInventory(): void {
        $this->initializeInventory();
    }

    public function getSelectedSlot(): int {
        return $this->selectedSlot;
    }

    public function setSelectedSlot(int $slot): void {
        if ($slot >= 0 && $slot < 9) {
            $this->selectedSlot = $slot;
        }
    }

    public function getItemInHand(): mixed {
        return $this->getInventoryItem($this->selectedSlot);
    }

    public function setItemInHand(mixed $item): void {
        $this->setInventoryItem($this->selectedSlot, $item);
    }

    public function getGameMode(): int {
        return $this->gameMode;
    }

    public function setGameMode(int $gameMode): void {
        if ($gameMode >= 0 && $gameMode <= 3) {
            $this->gameMode = $gameMode;
            $this->updateAbilitiesFromGameMode();
        }
    }

    private function updateAbilitiesFromGameMode(): void {
        switch ($this->gameMode) {
            case 0:
                $this->abilities['mayfly'] = false;
                $this->abilities['instabuild'] = false;
                $this->abilities['invulnerable'] = false;
                break;
            case 1:
                $this->abilities['mayfly'] = true;
                $this->abilities['instabuild'] = true;
                $this->abilities['invulnerable'] = true;
                break;
            case 2:
                $this->abilities['mayfly'] = true;
                $this->abilities['instabuild'] = false;
                $this->abilities['invulnerable'] = true;
                break;
            case 3:
                $this->abilities['mayfly'] = false;
                $this->abilities['instabuild'] = false;
                $this->abilities['invulnerable'] = false;
                break;
        }
    }

    public function getArmor(): array {
        return $this->armor;
    }

    public function setArmor(string $slot, mixed $item): void {
        if (isset($this->armor[$slot])) {
            $this->armor[$slot] = $item;
        }
    }

    public function getArmorItem(string $slot): mixed {
        return $this->armor[$slot] ?? null;
    }

    public function getHelmet(): mixed {
        return $this->armor['helmet'];
    }

    public function getChestplate(): mixed {
        return $this->armor['chestplate'];
    }

    public function getLeggings(): mixed {
        return $this->armor['leggings'];
    }

    public function getBoots(): mixed {
        return $this->armor['boots'];
    }

    public function getHunger(): float {
        return $this->hunger;
    }

    public function setHunger(float $hunger): void {
        $this->hunger = max(0, min(20, $hunger));
    }

    public function addHunger(float $amount): void {
        $this->setHunger($this->hunger + $amount);
    }

    public function getSaturation(): float {
        return $this->saturation;
    }

    public function setSaturation(float $saturation): void {
        $this->saturation = max(0, min(20, $saturation));
    }

    public function getExhaustion(): float {
        return $this->exhaustion;
    }

    public function setExhaustion(float $exhaustion): void {
        $this->exhaustion = max(0, $exhaustion);
    }

    public function addExhaustion(float $amount): void {
        $this->exhaustion += $amount;

        if ($this->exhaustion >= 4.0) {
            $this->exhaustion = 0;

            if ($this->saturation > 0) {
                $this->setSaturation($this->saturation - 1);
            } else {
                $this->setHunger($this->hunger - 1);
            }
        }
    }

    public function getExperience(): int {
        return $this->experience;
    }

    public function setExperience(int $experience): void {
        $this->experience = max(0, $experience);
    }

    public function addExperience(int $amount): void {
        $this->experience += $amount;
        $this->checkLevelUp();
    }

    public function getExperienceLevel(): int {
        return $this->experienceLevel;
    }

    public function setExperienceLevel(int $level): void {
        $this->experienceLevel = max(0, $level);
    }

    private function checkLevelUp(): void {
        $requiredXP = $this->getRequiredExperienceForLevel($this->experienceLevel + 1);

        while ($this->experience >= $requiredXP) {
            $this->experience -= $requiredXP;
            $this->experienceLevel++;
            $requiredXP = $this->getRequiredExperienceForLevel($this->experienceLevel + 1);
        }
    }

    private function getRequiredExperienceForLevel(int $level): int {
        if ($level <= 16) {
            return $level * $level + 6 * $level;
        } elseif ($level <= 31) {
            return (int)(2.5 * $level * $level - 40.5 * $level + 360);
        } else {
            return (int)(4.5 * $level * $level - 162.5 * $level + 2220);
        }
    }

    public function isOp(): bool {
        return $this->isOp;
    }

    public function setOp(bool $op): void {
        $this->isOp = $op;
    }

    public function hasPermission(string $permission): bool {
        if ($this->isOp) {
            return true;
        }
        return in_array($permission, $this->permissions, true);
    }

    public function addPermission(string $permission): void {
        if (!in_array($permission, $this->permissions, true)) {
            $this->permissions[] = $permission;
        }
    }

    public function removePermission(string $permission): void {
        $key = array_search($permission, $this->permissions, true);
        if ($key !== false) {
            unset($this->permissions[$key]);
            $this->permissions = array_values($this->permissions);
        }
    }

    public function getPermissions(): array {
        return $this->permissions;
    }

    public function getAbilities(): array {
        return $this->abilities;
    }

    public function setAbility(string $ability, bool $value): void {
        if (isset($this->abilities[$ability])) {
            $this->abilities[$ability] = $value;
        }
    }

    public function hasAbility(string $ability): bool {
        return $this->abilities[$ability] ?? false;
    }

    public function canFly(): bool {
        return $this->abilities['mayfly'] ?? false;
    }

    public function isFlying(): bool {
        return $this->abilities['flying'] ?? false;
    }

    public function setFlying(bool $flying): void {
        if ($this->canFly()) {
            $this->abilities['flying'] = $flying;
        }
    }

    public function tick(): void {
        parent::tick();

        if ($this->hunger <= 0) {
            $this->damage(1.0);
        }

        if ($this->isSprinting()) {
            $this->addExhaustion(0.1);
        }
    }

    public function saveToArray(): array {
        $data = parent::saveToArray();
        $data['username'] = $this->username;
        $data['uuid'] = $this->uuid;
        $data['xuid'] = $this->xuid;
        $data['inventory'] = $this->inventory;
        $data['selectedSlot'] = $this->selectedSlot;
        $data['gameMode'] = $this->gameMode;
        $data['armor'] = $this->armor;
        $data['hunger'] = $this->hunger;
        $data['saturation'] = $this->saturation;
        $data['exhaustion'] = $this->exhaustion;
        $data['experience'] = $this->experience;
        $data['experienceLevel'] = $this->experienceLevel;
        $data['permissions'] = $this->permissions;
        $data['isOp'] = $this->isOp;
        $data['abilities'] = $this->abilities;
        return $data;
    }

    public function loadFromArray(array $data): void {
        parent::loadFromArray($data);
        $this->username = $data['username'] ?? "";
        $this->uuid = $data['uuid'] ?? "";
        $this->xuid = $data['xuid'] ?? "";
        $this->inventory = $data['inventory'] ?? [];
        $this->selectedSlot = $data['selectedSlot'] ?? 0;
        $this->gameMode = $data['gameMode'] ?? 0;
        $this->armor = $data['armor'] ?? [];
        $this->hunger = $data['hunger'] ?? 20.0;
        $this->saturation = $data['saturation'] ?? 5.0;
        $this->exhaustion = $data['exhaustion'] ?? 0.0;
        $this->experience = $data['experience'] ?? 0;
        $this->experienceLevel = $data['experienceLevel'] ?? 0;
        $this->permissions = $data['permissions'] ?? [];
        $this->isOp = $data['isOp'] ?? false;
        $this->abilities = $data['abilities'] ?? [];
    }
}