<?php
declare(strict_types=1);

namespace MultiVersion\Entity;

use MultiVersion\World\World;

abstract class Entity {

    protected int $entityId;
    protected int $runtimeId;
    protected string $nameTag = "";
    protected float $x = 0.0;
    protected float $y = 0.0;
    protected float $z = 0.0;
    protected float $yaw = 0.0;
    protected float $pitch = 0.0;
    protected float $headYaw = 0.0;
    protected float $motionX = 0.0;
    protected float $motionY = 0.0;
    protected float $motionZ = 0.0;
    protected float $width = 0.6;
    protected float $height = 1.8;
    protected bool $onGround = false;
    protected bool $hasGravity = true;
    protected bool $canCollide = true;
    protected bool $isAlive = true;
    protected bool $isSneaking = false;
    protected bool $isSprinting = false;
    protected bool $isGliding = false;
    protected bool $isSwimming = false;
    protected ?World $world = null;
    protected array $metadata = [];
    protected array $dataProperties = [];
    protected float $lastUpdate = 0.0;
    protected int $age = 0;
    protected float $fallDistance = 0.0;

    public function __construct(int $entityId, int $runtimeId) {
        $this->entityId = $entityId;
        $this->runtimeId = $runtimeId;
        $this->lastUpdate = microtime(true);
        $this->initEntity();
    }

    protected function initEntity(): void {
        $this->setDataProperty(0, 0);
        $this->setDataProperty(1, 300);
        $this->setDataProperty(2, "");
        $this->setDataProperty(3, 1);
        $this->setDataProperty(4, 0);
        $this->setDataProperty(5, 1);
    }

    abstract public function getNetworkTypeId(): int;

    public function getEntityId(): int {
        return $this->entityId;
    }

    public function getRuntimeId(): int {
        return $this->runtimeId;
    }

    public function getNameTag(): string {
        return $this->nameTag;
    }

    public function setNameTag(string $nameTag): void {
        $this->nameTag = $nameTag;
        $this->setDataProperty(2, $nameTag);
    }

    public function getPosition(): array {
        return ['x' => $this->x, 'y' => $this->y, 'z' => $this->z];
    }

    public function setPosition(float $x, float $y, float $z): void {
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
    }

    public function getX(): float {
        return $this->x;
    }

    public function getY(): float {
        return $this->y;
    }

    public function getZ(): float {
        return $this->z;
    }

    public function getYaw(): float {
        return $this->yaw;
    }

    public function setYaw(float $yaw): void {
        $this->yaw = $yaw;
    }

    public function getPitch(): float {
        return $this->pitch;
    }

    public function setPitch(float $pitch): void {
        $this->pitch = $pitch;
    }

    public function getHeadYaw(): float {
        return $this->headYaw;
    }

    public function setHeadYaw(float $headYaw): void {
        $this->headYaw = $headYaw;
    }

    public function getMotion(): array {
        return ['x' => $this->motionX, 'y' => $this->motionY, 'z' => $this->motionZ];
    }

    public function setMotion(float $x, float $y, float $z): void {
        $this->motionX = $x;
        $this->motionY = $y;
        $this->motionZ = $z;
    }

    public function addMotion(float $x, float $y, float $z): void {
        $this->motionX += $x;
        $this->motionY += $y;
        $this->motionZ += $z;
    }

    public function getWidth(): float {
        return $this->width;
    }

    public function getHeight(): float {
        return $this->height;
    }

    public function isOnGround(): bool {
        return $this->onGround;
    }

    public function setOnGround(bool $onGround): void {
        $this->onGround = $onGround;
    }

    public function hasGravity(): bool {
        return $this->hasGravity;
    }

    public function setHasGravity(bool $gravity): void {
        $this->hasGravity = $gravity;
    }

    public function canCollide(): bool {
        return $this->canCollide;
    }

    public function setCanCollide(bool $collide): void {
        $this->canCollide = $collide;
    }

    public function isAlive(): bool {
        return $this->isAlive;
    }

    public function kill(): void {
        $this->isAlive = false;
    }

    public function isSneaking(): bool {
        return $this->isSneaking;
    }

    public function setSneaking(bool $sneaking): void {
        $this->isSneaking = $sneaking;
        $this->updateDataFlags();
    }

    public function isSprinting(): bool {
        return $this->isSprinting;
    }

    public function setSprinting(bool $sprinting): void {
        $this->isSprinting = $sprinting;
        $this->updateDataFlags();
    }

    public function isGliding(): bool {
        return $this->isGliding;
    }

    public function setGliding(bool $gliding): void {
        $this->isGliding = $gliding;
        $this->updateDataFlags();
    }

    public function isSwimming(): bool {
        return $this->isSwimming;
    }

    public function setSwimming(bool $swimming): void {
        $this->isSwimming = $swimming;
        $this->updateDataFlags();
    }

    protected function updateDataFlags(): void {
        $flags = 0;
        if ($this->onGround) $flags |= (1 << 0);
        if ($this->isSneaking) $flags |= (1 << 1);
        if ($this->isSprinting) $flags |= (1 << 3);
        if ($this->isGliding) $flags |= (1 << 7);
        if ($this->isSwimming) $flags |= (1 << 8);

        $this->setDataProperty(0, $flags);
    }

    public function getWorld(): ?World {
        return $this->world;
    }

    public function setWorld(?World $world): void {
        $this->world = $world;
    }

    public function setDataProperty(int $key, mixed $value): void {
        $this->dataProperties[$key] = $value;
    }

    public function getDataProperty(int $key): mixed {
        return $this->dataProperties[$key] ?? null;
    }

    public function getAllDataProperties(): array {
        return $this->dataProperties;
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

    public function getAge(): int {
        return $this->age;
    }

    public function getFallDistance(): float {
        return $this->fallDistance;
    }

    public function setFallDistance(float $distance): void {
        $this->fallDistance = $distance;
    }

    public function resetFallDistance(): void {
        $this->fallDistance = 0.0;
    }

    public function tick(): void {
        if (!$this->isAlive) {
            return;
        }

        $this->age++;

        if ($this->hasGravity && !$this->onGround) {
            $this->motionY -= 0.08;
        }

        $this->move($this->motionX, $this->motionY, $this->motionZ);

        $this->motionX *= 0.98;
        $this->motionY *= 0.98;
        $this->motionZ *= 0.98;

        if ($this->onGround) {
            $this->motionX *= 0.6;
            $this->motionZ *= 0.6;
        }

        $this->lastUpdate = microtime(true);
    }

    protected function move(float $dx, float $dy, float $dz): void {
        $this->x += $dx;
        $this->y += $dy;
        $this->z += $dz;

        if ($dy < 0) {
            $this->fallDistance += abs($dy);
        }

        $this->checkGround();
    }

    protected function checkGround(): void {
        if ($this->world === null) {
            return;
        }

        $block = $this->world->getBlock((int)floor($this->x), (int)floor($this->y - 0.1), (int)floor($this->z));
        $this->onGround = $block->isSolid();

        if ($this->onGround) {
            $this->resetFallDistance();
        }
    }

    public function teleport(float $x, float $y, float $z, ?float $yaw = null, ?float $pitch = null): void {
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;

        if ($yaw !== null) {
            $this->yaw = $yaw;
        }

        if ($pitch !== null) {
            $this->pitch = $pitch;
        }

        $this->resetFallDistance();
    }

    public function lookAt(float $targetX, float $targetY, float $targetZ): void {
        $dx = $targetX - $this->x;
        $dy = $targetY - $this->y;
        $dz = $targetZ - $this->z;

        $horizontalDistance = sqrt($dx * $dx + $dz * $dz);

        $this->yaw = atan2($dz, $dx) * 180 / M_PI - 90;
        $this->pitch = atan2($dy, $horizontalDistance) * 180 / M_PI * -1;
        $this->headYaw = $this->yaw;
    }

    public function getDistanceTo(Entity $entity): float {
        $dx = $this->x - $entity->x;
        $dy = $this->y - $entity->y;
        $dz = $this->z - $entity->z;

        return sqrt($dx * $dx + $dy * $dy + $dz * $dz);
    }

    public function getDistanceSquared(Entity $entity): float {
        $dx = $this->x - $entity->x;
        $dy = $this->y - $entity->y;
        $dz = $this->z - $entity->z;

        return $dx * $dx + $dy * $dy + $dz * $dz;
    }

    public function getBoundingBox(): array {
        $halfWidth = $this->width / 2;

        return [
            'minX' => $this->x - $halfWidth,
            'minY' => $this->y,
            'minZ' => $this->z - $halfWidth,
            'maxX' => $this->x + $halfWidth,
            'maxY' => $this->y + $this->height,
            'maxZ' => $this->z + $halfWidth
        ];
    }

    public function isColliding(Entity $entity): bool {
        if (!$this->canCollide || !$entity->canCollide) {
            return false;
        }

        $box1 = $this->getBoundingBox();
        $box2 = $entity->getBoundingBox();

        return !($box1['maxX'] < $box2['minX'] || $box1['minX'] > $box2['maxX'] ||
            $box1['maxY'] < $box2['minY'] || $box1['minY'] > $box2['maxY'] ||
            $box1['maxZ'] < $box2['minZ'] || $box1['minZ'] > $box2['maxZ']);
    }

    public function saveToArray(): array {
        return [
            'entityId' => $this->entityId,
            'runtimeId' => $this->runtimeId,
            'nameTag' => $this->nameTag,
            'x' => $this->x,
            'y' => $this->y,
            'z' => $this->z,
            'yaw' => $this->yaw,
            'pitch' => $this->pitch,
            'headYaw' => $this->headYaw,
            'motionX' => $this->motionX,
            'motionY' => $this->motionY,
            'motionZ' => $this->motionZ,
            'onGround' => $this->onGround,
            'isAlive' => $this->isAlive,
            'age' => $this->age,
            'metadata' => $this->metadata,
            'dataProperties' => $this->dataProperties
        ];
    }

    public function loadFromArray(array $data): void {
        $this->nameTag = $data['nameTag'] ?? "";
        $this->x = $data['x'] ?? 0.0;
        $this->y = $data['y'] ?? 0.0;
        $this->z = $data['z'] ?? 0.0;
        $this->yaw = $data['yaw'] ?? 0.0;
        $this->pitch = $data['pitch'] ?? 0.0;
        $this->headYaw = $data['headYaw'] ?? 0.0;
        $this->motionX = $data['motionX'] ?? 0.0;
        $this->motionY = $data['motionY'] ?? 0.0;
        $this->motionZ = $data['motionZ'] ?? 0.0;
        $this->onGround = $data['onGround'] ?? false;
        $this->isAlive = $data['isAlive'] ?? true;
        $this->age = $data['age'] ?? 0;
        $this->metadata = $data['metadata'] ?? [];
        $this->dataProperties = $data['dataProperties'] ?? [];
    }

    public function __toString(): string {
        return "Entity(id={$this->entityId}, runtime={$this->runtimeId}, pos=[{$this->x},{$this->y},{$this->z}])";
    }

    public function close(): void {
        $this->isAlive = false;
        $this->world = null;
    }
}