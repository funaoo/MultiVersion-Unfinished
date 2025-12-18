<?php
declare(strict_types=1);

namespace MultiVersion\Player;

use MultiVersion\MultiVersion;
use MultiVersion\Network\PlayerSession;
use pocketmine\player\Player as PMPlayer;
use pocketmine\Server;

class Player {

    private string $username;
    private string $uuid;
    private string $xuid;
    private PMPlayer $pmPlayer;
    private MultiVersion $plugin;
    private ?PlayerSession $session = null;
    private PlayerInfo $info;
    private PlayerInventory $inventory;
    private PlayerAbilities $abilities;
    private int $protocol;
    private string $protocolVersion;
    private float $joinTime;
    private array $metadata = [];
    private array $permissions = [];
    private bool $isOp = false;
    private int $gameMode = 0;
    private float $health = 20.0;
    private float $maxHealth = 20.0;
    private float $hunger = 20.0;
    private float $saturation = 5.0;
    private int $experience = 0;
    private int $experienceLevel = 0;
    private array $effects = [];
    private bool $isFlying = false;
    private bool $isSneaking = false;
    private bool $isSprinting = false;
    private bool $isGliding = false;
    private bool $isSwimming = false;
    private array $spawnPosition = [];
    private array $lastPosition = [];
    private float $lastUpdate = 0.0;

    public function __construct(PMPlayer $pmPlayer, MultiVersion $plugin, int $protocol) {
        $this->pmPlayer = $pmPlayer;
        $this->plugin = $plugin;
        $this->protocol = $protocol;
        $this->username = $pmPlayer->getName();
        $this->uuid = $pmPlayer->getUniqueId()->toString();
        $this->joinTime = microtime(true);
        $this->lastUpdate = microtime(true);

        $this->info = new PlayerInfo($this);
        $this->inventory = new PlayerInventory($this);
        $this->abilities = new PlayerAbilities($this);

        $this->initializePlayer();
    }

    private function initializePlayer(): void {
        $playerInfo = $this->pmPlayer->getPlayerInfo();
        $this->xuid = $playerInfo->getXuid();

        $protocolInterface = $this->plugin->getVersionRegistry()->getProtocolInterface($this->protocol);
        if ($protocolInterface !== null) {
            $this->protocolVersion = $protocolInterface->getMinecraftVersion();
        } else {
            $this->protocolVersion = 'Unknown';
        }

        $this->gameMode = $this->pmPlayer->getGamemode()->id();
        $this->health = $this->pmPlayer->getHealth();
        $this->maxHealth = $this->pmPlayer->getMaxHealth();
        $this->isOp = $this->pmPlayer->hasPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE);

        $position = $this->pmPlayer->getPosition();
        $this->lastPosition = [
            'x' => $position->getX(),
            'y' => $position->getY(),
            'z' => $position->getZ(),
            'yaw' => $this->pmPlayer->getLocation()->getYaw(),
            'pitch' => $this->pmPlayer->getLocation()->getPitch()
        ];
    }

    public function getUsername(): string {
        return $this->username;
    }

    public function getUuid(): string {
        return $this->uuid;
    }

    public function getXuid(): string {
        return $this->xuid;
    }

    public function getPMPlayer(): PMPlayer {
        return $this->pmPlayer;
    }

    public function getSession(): ?PlayerSession {
        return $this->session;
    }

    public function setSession(PlayerSession $session): void {
        $this->session = $session;
    }

    public function getInfo(): PlayerInfo {
        return $this->info;
    }

    public function getInventory(): PlayerInventory {
        return $this->inventory;
    }

    public function getAbilities(): PlayerAbilities {
        return $this->abilities;
    }

    public function getProtocol(): int {
        return $this->protocol;
    }

    public function getProtocolVersion(): string {
        return $this->protocolVersion;
    }

    public function getJoinTime(): float {
        return $this->joinTime;
    }

    public function getPlayTime(): float {
        return microtime(true) - $this->joinTime;
    }

    public function setMetadata(string $key, mixed $value): void {
        $this->metadata[$key] = $value;
    }

    public function getMetadata(string $key): mixed {
        return $this->metadata[$key] ?? null;
    }

    public function hasMetadata(string $key): bool {
        return isset($this->metadata[$key]);
    }

    public function removeMetadata(string $key): void {
        unset($this->metadata[$key]);
    }

    public function getAllMetadata(): array {
        return $this->metadata;
    }

    public function hasPermission(string $permission): bool {
        if ($this->isOp) {
            return true;
        }
        return in_array($permission, $this->permissions, true) || $this->pmPlayer->hasPermission($permission);
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

    public function isOp(): bool {
        return $this->isOp;
    }

    public function setOp(bool $op): void {
        $this->isOp = $op;
        if ($op) {
            $this->pmPlayer->setOp(true);
        }
    }

    public function getGameMode(): int {
        return $this->gameMode;
    }

    public function setGameMode(int $gameMode): void {
        if ($gameMode >= 0 && $gameMode <= 3) {
            $this->gameMode = $gameMode;
            $this->abilities->updateFromGameMode($gameMode);
        }
    }

    public function getHealth(): float {
        return $this->health;
    }

    public function setHealth(float $health): void {
        $this->health = max(0, min($this->maxHealth, $health));
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
        $this->setHealth($this->health - $amount);
    }

    public function getHunger(): float {
        return $this->hunger;
    }

    public function setHunger(float $hunger): void {
        $this->hunger = max(0, min(20, $hunger));
    }

    public function getSaturation(): float {
        return $this->saturation;
    }

    public function setSaturation(float $saturation): void {
        $this->saturation = max(0, min(20, $saturation));
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

    public function addEffect(int $effectId, int $duration, int $amplifier = 0): void {
        $this->effects[$effectId] = [
            'duration' => $duration,
            'amplifier' => $amplifier,
            'start_time' => microtime(true)
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

    public function isFlying(): bool {
        return $this->isFlying;
    }

    public function setFlying(bool $flying): void {
        if ($this->abilities->canFly()) {
            $this->isFlying = $flying;
        }
    }

    public function isSneaking(): bool {
        return $this->isSneaking;
    }

    public function setSneaking(bool $sneaking): void {
        $this->isSneaking = $sneaking;
    }

    public function isSprinting(): bool {
        return $this->isSprinting;
    }

    public function setSprinting(bool $sprinting): void {
        $this->isSprinting = $sprinting;
    }

    public function isGliding(): bool {
        return $this->isGliding;
    }

    public function setGliding(bool $gliding): void {
        $this->isGliding = $gliding;
    }

    public function isSwimming(): bool {
        return $this->isSwimming;
    }

    public function setSwimming(bool $swimming): void {
        $this->isSwimming = $swimming;
    }

    public function getSpawnPosition(): array {
        if (empty($this->spawnPosition)) {
            $spawn = $this->pmPlayer->getWorld()->getSpawnLocation();
            $this->spawnPosition = [
                'x' => $spawn->getX(),
                'y' => $spawn->getY(),
                'z' => $spawn->getZ()
            ];
        }
        return $this->spawnPosition;
    }

    public function setSpawnPosition(float $x, float $y, float $z): void {
        $this->spawnPosition = ['x' => $x, 'y' => $y, 'z' => $z];
    }

    public function getLastPosition(): array {
        return $this->lastPosition;
    }

    public function updatePosition(float $x, float $y, float $z, float $yaw, float $pitch): void {
        $this->lastPosition = [
            'x' => $x,
            'y' => $y,
            'z' => $z,
            'yaw' => $yaw,
            'pitch' => $pitch
        ];
        $this->lastUpdate = microtime(true);
    }

    public function getLastUpdate(): float {
        return $this->lastUpdate;
    }

    public function sendMessage(string $message): void {
        $this->pmPlayer->sendMessage($message);
    }

    public function sendPopup(string $message): void {
        $this->pmPlayer->sendPopup($message);
    }

    public function sendTip(string $message): void {
        $this->pmPlayer->sendTip($message);
    }

    public function kick(string $reason = ""): void {
        $this->pmPlayer->kick($reason);
    }

    public function teleport(float $x, float $y, float $z): void {
        $world = $this->pmPlayer->getWorld();
        $this->pmPlayer->teleport(new \pocketmine\world\Position($x, $y, $z, $world));
        $this->updatePosition($x, $y, $z, $this->lastPosition['yaw'], $this->lastPosition['pitch']);
    }

    public function isOnline(): bool {
        return $this->pmPlayer->isOnline();
    }

    public function isConnected(): bool {
        return $this->pmPlayer->isConnected();
    }

    public function save(): array {
        return [
            'username' => $this->username,
            'uuid' => $this->uuid,
            'xuid' => $this->xuid,
            'protocol' => $this->protocol,
            'protocol_version' => $this->protocolVersion,
            'join_time' => $this->joinTime,
            'play_time' => $this->getPlayTime(),
            'metadata' => $this->metadata,
            'permissions' => $this->permissions,
            'is_op' => $this->isOp,
            'game_mode' => $this->gameMode,
            'health' => $this->health,
            'max_health' => $this->maxHealth,
            'hunger' => $this->hunger,
            'saturation' => $this->saturation,
            'experience' => $this->experience,
            'experience_level' => $this->experienceLevel,
            'effects' => $this->effects,
            'spawn_position' => $this->spawnPosition,
            'last_position' => $this->lastPosition,
            'info' => $this->info->save(),
            'inventory' => $this->inventory->save(),
            'abilities' => $this->abilities->save()
        ];
    }

    public function getStatistics(): array {
        return [
            'username' => $this->username,
            'protocol' => $this->protocol,
            'version' => $this->protocolVersion,
            'play_time' => round($this->getPlayTime(), 2),
            'packets_sent' => $this->session?->getPacketsSent() ?? 0,
            'packets_received' => $this->session?->getPacketsReceived() ?? 0,
            'health' => $this->health,
            'level' => $this->experienceLevel,
            'game_mode' => $this->gameMode
        ];
    }
}