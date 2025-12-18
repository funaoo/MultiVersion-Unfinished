<?php
declare(strict_types=1);

namespace MultiVersion\Player;

class PlayerAbilities {

    private Player $player;
    private bool $canFly = false;
    private bool $isFlying = false;
    private bool $canInstantBuild = false;
    private bool $isInvulnerable = false;
    private bool $isMuted = false;
    private bool $isWorldBuilder = false;
    private bool $hasNoClip = false;
    private float $walkSpeed = 0.1;
    private float $flySpeed = 0.05;
    private array $customAbilities = [];

    public function __construct(Player $player) {
        $this->player = $player;
        $this->initializeAbilities();
    }

    private function initializeAbilities(): void {
        $gameMode = $this->player->getGameMode();
        $this->updateFromGameMode($gameMode);
    }

    public function updateFromGameMode(int $gameMode): void {
        switch ($gameMode) {
            case 0:
                $this->canFly = false;
                $this->isFlying = false;
                $this->canInstantBuild = false;
                $this->isInvulnerable = false;
                break;
            case 1:
                $this->canFly = true;
                $this->canInstantBuild = true;
                $this->isInvulnerable = true;
                break;
            case 2:
                $this->canFly = true;
                $this->isFlying = true;
                $this->canInstantBuild = false;
                $this->isInvulnerable = true;
                break;
            case 3:
                $this->canFly = false;
                $this->isFlying = false;
                $this->canInstantBuild = false;
                $this->isInvulnerable = false;
                break;
        }
    }

    public function canFly(): bool {
        return $this->canFly;
    }

    public function setCanFly(bool $canFly): void {
        $this->canFly = $canFly;
        if (!$canFly) {
            $this->isFlying = false;
        }
    }

    public function isFlying(): bool {
        return $this->isFlying;
    }

    public function setFlying(bool $flying): void {
        if ($this->canFly) {
            $this->isFlying = $flying;
        }
    }

    public function canInstantBuild(): bool {
        return $this->canInstantBuild;
    }

    public function setCanInstantBuild(bool $canInstantBuild): void {
        $this->canInstantBuild = $canInstantBuild;
    }

    public function isInvulnerable(): bool {
        return $this->isInvulnerable;
    }

    public function setInvulnerable(bool $invulnerable): void {
        $this->isInvulnerable = $invulnerable;
    }

    public function isMuted(): bool {
        return $this->isMuted;
    }

    public function setMuted(bool $muted): void {
        $this->isMuted = $muted;
    }

    public function isWorldBuilder(): bool {
        return $this->isWorldBuilder;
    }

    public function setWorldBuilder(bool $worldBuilder): void {
        $this->isWorldBuilder = $worldBuilder;
    }

    public function hasNoClip(): bool {
        return $this->hasNoClip;
    }

    public function setNoClip(bool $noClip): void {
        $this->hasNoClip = $noClip;
    }

    public function getWalkSpeed(): float {
        return $this->walkSpeed;
    }

    public function setWalkSpeed(float $speed): void {
        $this->walkSpeed = max(0, min(1, $speed));
    }

    public function getFlySpeed(): float {
        return $this->flySpeed;
    }

    public function setFlySpeed(float $speed): void {
        $this->flySpeed = max(0, min(1, $speed));
    }

    public function setCustomAbility(string $key, mixed $value): void {
        $this->customAbilities[$key] = $value;
    }

    public function getCustomAbility(string $key): mixed {
        return $this->customAbilities[$key] ?? null;
    }

    public function hasCustomAbility(string $key): bool {
        return isset($this->customAbilities[$key]);
    }

    public function removeCustomAbility(string $key): void {
        unset($this->customAbilities[$key]);
    }

    public function getAllCustomAbilities(): array {
        return $this->customAbilities;
    }

    public function resetToDefaults(): void {
        $this->canFly = false;
        $this->isFlying = false;
        $this->canInstantBuild = false;
        $this->isInvulnerable = false;
        $this->isMuted = false;
        $this->isWorldBuilder = false;
        $this->hasNoClip = false;
        $this->walkSpeed = 0.1;
        $this->flySpeed = 0.05;
        $this->customAbilities = [];
    }

    public function toArray(): array {
        return [
            'can_fly' => $this->canFly,
            'is_flying' => $this->isFlying,
            'can_instant_build' => $this->canInstantBuild,
            'is_invulnerable' => $this->isInvulnerable,
            'is_muted' => $this->isMuted,
            'is_world_builder' => $this->isWorldBuilder,
            'has_no_clip' => $this->hasNoClip,
            'walk_speed' => $this->walkSpeed,
            'fly_speed' => $this->flySpeed,
            'custom_abilities' => $this->customAbilities
        ];
    }

    public function fromArray(array $data): void {
        $this->canFly = $data['can_fly'] ?? false;
        $this->isFlying = $data['is_flying'] ?? false;
        $this->canInstantBuild = $data['can_instant_build'] ?? false;
        $this->isInvulnerable = $data['is_invulnerable'] ?? false;
        $this->isMuted = $data['is_muted'] ?? false;
        $this->isWorldBuilder = $data['is_world_builder'] ?? false;
        $this->hasNoClip = $data['has_no_clip'] ?? false;
        $this->walkSpeed = $data['walk_speed'] ?? 0.1;
        $this->flySpeed = $data['fly_speed'] ?? 0.05;
        $this->customAbilities = $data['custom_abilities'] ?? [];
    }

    public function save(): array {
        return $this->toArray();
    }

    public function getAbilityFlags(): int {
        $flags = 0;

        if ($this->canFly) $flags |= (1 << 0);
        if ($this->isFlying) $flags |= (1 << 1);
        if ($this->canInstantBuild) $flags |= (1 << 2);
        if ($this->isInvulnerable) $flags |= (1 << 3);
        if ($this->isMuted) $flags |= (1 << 4);
        if ($this->isWorldBuilder) $flags |= (1 << 5);
        if ($this->hasNoClip) $flags |= (1 << 6);

        return $flags;
    }

    public function setAbilityFlags(int $flags): void {
        $this->canFly = ($flags & (1 << 0)) !== 0;
        $this->isFlying = ($flags & (1 << 1)) !== 0;
        $this->canInstantBuild = ($flags & (1 << 2)) !== 0;
        $this->isInvulnerable = ($flags & (1 << 3)) !== 0;
        $this->isMuted = ($flags & (1 << 4)) !== 0;
        $this->isWorldBuilder = ($flags & (1 << 5)) !== 0;
        $this->hasNoClip = ($flags & (1 << 6)) !== 0;
    }
}