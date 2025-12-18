<?php

declare(strict_types=1);

namespace MultiVersion\Handler;

use MultiVersion\MultiVersion;
use MultiVersion\Network\PacketRegistry;
use MultiVersion\Network\PlayerSession;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\EntityEventPacket;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\RespawnPacket;
use pocketmine\network\mcpe\protocol\SetEntityDataPacket;
use pocketmine\network\mcpe\protocol\SetEntityMotionPacket;
use pocketmine\network\mcpe\protocol\TextPacket;

final class GameHandler extends PacketHandler {

    private array $lastPositions = [];
    private array $actionQueue = [];
    private array $interactionCooldowns = [];
    private int $cooldownTime = 100;

    protected function initialize(): void {
        $this->handledPackets = [
            MovePlayerPacket::class,
            PlayerActionPacket::class,
            InteractPacket::class,
            AnimatePacket::class,
            RespawnPacket::class,
            TextPacket::class,
            PlayerAuthInputPacket::class,
            SetEntityDataPacket::class,
            SetEntityMotionPacket::class,
            EntityEventPacket::class
        ];
    }

    public function register(PacketRegistry $registry): void {
        $this->registerPacket($registry, MovePlayerPacket::class,
            fn($packet, $session) => $this->handleMovePlayer($packet, $session), 10);

        $this->registerPacket($registry, PlayerActionPacket::class,
            fn($packet, $session) => $this->handlePlayerAction($packet, $session), 10);

        $this->registerPacket($registry, InteractPacket::class,
            fn($packet, $session) => $this->handleInteract($packet, $session), 10);

        $this->registerPacket($registry, AnimatePacket::class,
            fn($packet, $session) => $this->handleAnimate($packet, $session), 5);

        $this->registerPacket($registry, RespawnPacket::class,
            fn($packet, $session) => $this->handleRespawn($packet, $session), 10);

        $this->registerPacket($registry, TextPacket::class,
            fn($packet, $session) => $this->handleText($packet, $session), 10);

        $this->registerPacket($registry, PlayerAuthInputPacket::class,
            fn($packet, $session) => $this->handleAuthInput($packet, $session), 5);
    }

    private function handleMovePlayer(MovePlayerPacket $packet, PlayerSession $session): void {
        $player = $session->getPlayer();
        $playerName = $player->getName();

        if (!$this->validateMovement($packet, $session)) {
            $this->correctPlayerPosition($session);
            return;
        }

        $this->lastPositions[$playerName] = [
            'x' => $packet->position->x,
            'y' => $packet->position->y,
            'z' => $packet->position->z,
            'yaw' => $packet->yaw,
            'pitch' => $packet->pitch,
            'headYaw' => $packet->headYaw,
            'time' => microtime(true)
        ];

        $this->logPacket("Move: ({$packet->position->x}, {$packet->position->y}, {$packet->position->z})", $session);
    }

    private function validateMovement(MovePlayerPacket $packet, PlayerSession $session): bool {
        $player = $session->getPlayer();
        $playerName = $player->getName();

        if (!isset($this->lastPositions[$playerName])) {
            return true;
        }

        $lastPos = $this->lastPositions[$playerName];
        $distance = sqrt(
            pow($packet->position->x - $lastPos['x'], 2) +
            pow($packet->position->y - $lastPos['y'], 2) +
            pow($packet->position->z - $lastPos['z'], 2)
        );

        $maxDistance = 10.0;
        if ($distance > $maxDistance) {
            $this->logPacket("Movement validation failed: distance {$distance}", $session, 'warning');
            return false;
        }

        return true;
    }

    private function correctPlayerPosition(PlayerSession $session): void {
        $player = $session->getPlayer();
        $position = $player->getPosition();

        $packet = new \pocketmine\network\mcpe\protocol\CorrectPlayerMovePredictionPacket();
        $packet->position = $position->asVector3();
        $packet->delta = new \pocketmine\math\Vector3(0, 0, 0);
        $packet->onGround = $player->isOnGround();
        $packet->tick = $player->getWorld()->getTime();

        $session->sendPacket($packet);
        $this->logPacket("Position corrected", $session, 'warning');
    }

    private function handlePlayerAction(PlayerActionPacket $packet, PlayerSession $session): void {
        $player = $session->getPlayer();
        $action = $packet->action;

        $this->queueAction($player->getName(), $action, $packet);

        match($action) {
            PlayerActionPacket::ACTION_START_BREAK => $this->handleStartBreak($packet, $session),
            PlayerActionPacket::ACTION_ABORT_BREAK => $this->handleAbortBreak($packet, $session),
            PlayerActionPacket::ACTION_STOP_BREAK => $this->handleStopBreak($packet, $session),
            PlayerActionPacket::ACTION_START_SPRINT => $this->handleStartSprint($packet, $session),
            PlayerActionPacket::ACTION_STOP_SPRINT => $this->handleStopSprint($packet, $session),
            PlayerActionPacket::ACTION_START_SNEAK => $this->handleStartSneak($packet, $session),
            PlayerActionPacket::ACTION_STOP_SNEAK => $this->handleStopSneak($packet, $session),
            PlayerActionPacket::ACTION_START_GLIDE => $this->handleStartGlide($packet, $session),
            PlayerActionPacket::ACTION_STOP_GLIDE => $this->handleStopGlide($packet, $session),
            PlayerActionPacket::ACTION_JUMP => $this->handleJump($packet, $session),
            default => $this->logPacket("Unknown action: {$action}", $session, 'warning')
        };
    }

    private function queueAction(string $playerName, int $action, DataPacket $packet): void {
        if (!isset($this->actionQueue[$playerName])) {
            $this->actionQueue[$playerName] = [];
        }

        $this->actionQueue[$playerName][] = [
            'action' => $action,
            'packet' => $packet,
            'time' => microtime(true)
        ];

        if (count($this->actionQueue[$playerName]) > 100) {
            array_shift($this->actionQueue[$playerName]);
        }
    }

    private function handleStartBreak(PlayerActionPacket $packet, PlayerSession $session): void {
        $this->logPacket("Start breaking block at ({$packet->blockPosition->x}, {$packet->blockPosition->y}, {$packet->blockPosition->z})", $session);
    }

    private function handleAbortBreak(PlayerActionPacket $packet, PlayerSession $session): void {
        $this->logPacket("Abort breaking block", $session);
    }

    private function handleStopBreak(PlayerActionPacket $packet, PlayerSession $session): void {
        $this->logPacket("Stop breaking block", $session);
    }

    private function handleStartSprint(PlayerActionPacket $packet, PlayerSession $session): void {
        $player = $session->getPlayer();
        $player->setSprinting(true);
        $this->logPacket("Started sprinting", $session);
    }

    private function handleStopSprint(PlayerActionPacket $packet, PlayerSession $session): void {
        $player = $session->getPlayer();
        $player->setSprinting(false);
        $this->logPacket("Stopped sprinting", $session);
    }

    private function handleStartSneak(PlayerActionPacket $packet, PlayerSession $session): void {
        $player = $session->getPlayer();
        $player->setSneaking(true);
        $this->logPacket("Started sneaking", $session);
    }

    private function handleStopSneak(PlayerActionPacket $packet, PlayerSession $session): void {
        $player = $session->getPlayer();
        $player->setSneaking(false);
        $this->logPacket("Stopped sneaking", $session);
    }

    private function handleStartGlide(PlayerActionPacket $packet, PlayerSession $session): void {
        $player = $session->getPlayer();
        $player->setGliding(true);
        $this->logPacket("Started gliding", $session);
    }

    private function handleStopGlide(PlayerActionPacket $packet, PlayerSession $session): void {
        $player = $session->getPlayer();
        $player->setGliding(false);
        $this->logPacket("Stopped gliding", $session);
    }

    private function handleJump(PlayerActionPacket $packet, PlayerSession $session): void {
        $this->logPacket("Jump", $session);
    }

    private function handleInteract(InteractPacket $packet, PlayerSession $session): void {
        $player = $session->getPlayer();
        $playerName = $player->getName();

        if ($this->isOnCooldown($playerName)) {
            return;
        }

        $this->setCooldown($playerName);
        $action = $packet->action;

        match($action) {
            InteractPacket::ACTION_LEAVE_VEHICLE => $this->handleLeaveVehicle($packet, $session),
            InteractPacket::ACTION_MOUSEOVER => $this->handleMouseOver($packet, $session),
            InteractPacket::ACTION_OPEN_INVENTORY => $this->handleOpenInventory($packet, $session),
            default => $this->logPacket("Unknown interact action: {$action}", $session, 'warning')
        };
    }

    private function isOnCooldown(string $playerName): bool {
        if (!isset($this->interactionCooldowns[$playerName])) {
            return false;
        }

        $elapsed = (microtime(true) - $this->interactionCooldowns[$playerName]) * 1000;
        return $elapsed < $this->cooldownTime;
    }

    private function setCooldown(string $playerName): void {
        $this->interactionCooldowns[$playerName] = microtime(true);
    }

    private function handleLeaveVehicle(InteractPacket $packet, PlayerSession $session): void {
        $this->logPacket("Leave vehicle", $session);
    }

    private function handleMouseOver(InteractPacket $packet, PlayerSession $session): void {
        $this->logPacket("Mouse over entity: {$packet->targetActorRuntimeId}", $session);
    }

    private function handleOpenInventory(InteractPacket $packet, PlayerSession $session): void {
        $this->logPacket("Open inventory", $session);
    }

    private function handleAnimate(AnimatePacket $packet, PlayerSession $session): void {
        $action = $packet->action;
        $this->logPacket("Animate action: {$action}", $session);
    }

    private function handleRespawn(RespawnPacket $packet, PlayerSession $session): void {
        $player = $session->getPlayer();
        $state = $packet->respawnState;

        if ($state === RespawnPacket::CLIENT_READY_TO_SPAWN) {
            $this->logPacket("Client ready to spawn", $session, 'info');
        }
    }

    private function handleText(TextPacket $packet, PlayerSession $session): void {
        $player = $session->getPlayer();
        $type = $packet->type;
        $message = $packet->message;

        if ($type === TextPacket::TYPE_CHAT) {
            $this->logPacket("Chat: {$message}", $session);
        }
    }

    private function handleAuthInput(PlayerAuthInputPacket $packet, PlayerSession $session): void {
        $player = $session->getPlayer();
        $position = $packet->getPosition();

        $this->lastPositions[$player->getName()] = [
            'x' => $position->x,
            'y' => $position->y,
            'z' => $position->z,
            'yaw' => $packet->getYaw(),
            'pitch' => $packet->getPitch(),
            'headYaw' => $packet->getHeadYaw(),
            'time' => microtime(true)
        ];
    }

    public function getLastPosition(string $playerName): ?array {
        return $this->lastPositions[$playerName] ?? null;
    }

    public function clearPlayerData(string $playerName): void {
        unset($this->lastPositions[$playerName]);
        unset($this->actionQueue[$playerName]);
        unset($this->interactionCooldowns[$playerName]);
    }

    public function getActionQueue(string $playerName): array {
        return $this->actionQueue[$playerName] ?? [];
    }

    public function setCooldownTime(int $milliseconds): void {
        $this->cooldownTime = max(0, $milliseconds);
    }
}