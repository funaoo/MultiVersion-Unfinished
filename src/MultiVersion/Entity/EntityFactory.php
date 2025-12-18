<?php
declare(strict_types=1);

namespace MultiVersion\Entity;

use MultiVersion\Core\MultiVersion;
use MultiVersion\Entity\Types\Item;

final class EntityFactory {

    private static ?EntityFactory $instance = null;
    private MultiVersion $plugin;
    private array $entityTypes = [];
    private int $nextEntityId = 1;
    private int $nextRuntimeId = 1;

    public function __construct(MultiVersion $plugin) {
        $this->plugin = $plugin;
        self::$instance = $this;
        $this->registerDefaultEntities();
    }

    public static function getInstance(): ?EntityFactory {
        return self::$instance;
    }

    private function registerDefaultEntities(): void {
        $this->registerEntity(64, Item::class, "Item");
    }

    public function registerEntity(int $networkId, string $class, string $name): void {
        if (!class_exists($class)) {
            $this->plugin->getMVLogger()->error("Entity class does not exist: {$class}");
            return;
        }

        if (!is_subclass_of($class, Entity::class)) {
            $this->plugin->getMVLogger()->error("Entity class must extend Entity: {$class}");
            return;
        }

        $this->entityTypes[$networkId] = [
            'class' => $class,
            'name' => $name
        ];

        $this->plugin->getMVLogger()->debug("Registered entity: {$name} (ID: {$networkId})");
    }

    public function createEntity(int $networkId, ?array $data = null): ?Entity {
        if (!isset($this->entityTypes[$networkId])) {
            $this->plugin->getMVLogger()->warning("Unknown entity type: {$networkId}");
            return null;
        }

        $entityId = $this->getNextEntityId();
        $runtimeId = $this->getNextRuntimeId();

        $class = $this->entityTypes[$networkId]['class'];

        try {
            $entity = new $class($entityId, $runtimeId);

            if ($data !== null) {
                $entity->loadFromArray($data);
            }

            $this->plugin->getMVLogger()->debug(
                "Created entity: {$this->entityTypes[$networkId]['name']} (ID: {$entityId}, Runtime: {$runtimeId})"
            );

            return $entity;
        } catch (\Exception $e) {
            $this->plugin->getMVLogger()->error(
                "Failed to create entity {$networkId}: {$e->getMessage()}"
            );
            return null;
        }
    }

    public function createEntityByName(string $name, ?array $data = null): ?Entity {
        foreach ($this->entityTypes as $networkId => $entityData) {
            if (strtolower($entityData['name']) === strtolower($name)) {
                return $this->createEntity($networkId, $data);
            }
        }

        $this->plugin->getMVLogger()->warning("Unknown entity name: {$name}");
        return null;
    }

    public function createItem(mixed $item, float $x, float $y, float $z): ?Item {
        $entity = $this->createEntity(64);

        if ($entity instanceof Item) {
            $entity->setItem($item);
            $entity->setPosition($x, $y, $z);
            return $entity;
        }

        return null;
    }

    private function getNextEntityId(): int {
        return $this->nextEntityId++;
    }

    private function getNextRuntimeId(): int {
        return $this->nextRuntimeId++;
    }

    public function isEntityRegistered(int $networkId): bool {
        return isset($this->entityTypes[$networkId]);
    }

    public function getEntityName(int $networkId): ?string {
        return $this->entityTypes[$networkId]['name'] ?? null;
    }

    public function getEntityClass(int $networkId): ?string {
        return $this->entityTypes[$networkId]['class'] ?? null;
    }

    public function getAllRegisteredEntities(): array {
        return $this->entityTypes;
    }

    public function getEntityCount(): int {
        return count($this->entityTypes);
    }

    public function resetEntityIds(): void {
        $this->nextEntityId = 1;
        $this->nextRuntimeId = 1;
    }

    public function getStatistics(): array {
        return [
            'registered_entities' => $this->getEntityCount(),
            'next_entity_id' => $this->nextEntityId,
            'next_runtime_id' => $this->nextRuntimeId
        ];
    }
}