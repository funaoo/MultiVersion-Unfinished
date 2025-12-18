<?php

declare(strict_types=1);

namespace MultiVersion\Handler;

use MultiVersion\MultiVersion;
use MultiVersion\Network\PacketRegistry;
use MultiVersion\Network\PlayerSession;
use pocketmine\network\mcpe\protocol\BiomeDefinitionListPacket;
use pocketmine\network\mcpe\protocol\ClientToServerHandshakePacket;
use pocketmine\network\mcpe\protocol\CreativeContentPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\ServerToClientHandshakePacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\network\mcpe\protocol\types\SpawnSettings;

final class LoginHandler extends PacketHandler {

    private array $loginQueue = [];
    private array $resourcePackResponses = [];

    protected function initialize(): void {
        $this->handledPackets = [
            LoginPacket::class,
            ClientToServerHandshakePacket::class,
            ResourcePackClientResponsePacket::class
        ];
    }

    public function register(PacketRegistry $registry): void {
        $this->registerPacket($registry, LoginPacket::class,
            fn($packet, $session) => $this->handleLogin($packet, $session), 100);

        $this->registerPacket($registry, ClientToServerHandshakePacket::class,
            fn($packet, $session) => $this->handleHandshake($packet, $session), 100);

        $this->registerPacket($registry, ResourcePackClientResponsePacket::class,
            fn($packet, $session) => $this->handleResourcePackResponse($packet, $session), 50);
    }

    private function handleLogin(DataPacket $packet, PlayerSession $session): void {
        $player = $session->getPlayer();
        $protocol = $session->getProtocol();

        $this->logPacket("Login packet received - Protocol: {$protocol}", $session, 'info');

        if (!$this->validateProtocol($protocol, $session)) {
            $this->disconnectPlayer($player, "Unsupported protocol version");
            return;
        }

        $this->queueLogin($player->getName(), $protocol);
        $this->sendPlayStatus($session, 0);
        $this->sendResourcePacksInfo($session);

        $event = new \MultiVersion\Events\PlayerJoinEvent($player, $protocol);
        $this->plugin->getEventDispatcher()->dispatch($event);
    }

    private function validateProtocol(int $protocol, PlayerSession $session): bool {
        $registry = $this->plugin->getVersionRegistry();

        if (!$registry->isProtocolSupported($protocol)) {
            $this->logPacket("Unsupported protocol: {$protocol}", $session, 'warning');
            return false;
        }

        return true;
    }

    private function queueLogin(string $playerName, int $protocol): void {
        $this->loginQueue[$playerName] = [
            'protocol' => $protocol,
            'time' => microtime(true),
            'stage' => 'login'
        ];
    }

    private function sendPlayStatus(PlayerSession $session, int $status): void {
        $packet = new PlayStatusPacket();
        $packet->status = $status;
        $session->sendPacket($packet);

        $this->logPacket("Sent PlayStatus: {$status}", $session);
    }

    private function sendResourcePacksInfo(PlayerSession $session): void {
        $packet = new ResourcePacksInfoPacket();
        $packet->resourcePackEntries = [];
        $packet->mustAccept = false;
        $packet->hasScripts = false;
        $packet->forceAccept = false;

        $session->sendPacket($packet);
        $this->logPacket("Sent ResourcePacksInfo", $session);
    }

    private function handleHandshake(DataPacket $packet, PlayerSession $session): void {
        $player = $session->getPlayer();
        $playerName = $player->getName();

        if (!isset($this->loginQueue[$playerName])) {
            $this->logPacket("Handshake without login", $session, 'warning');
            return;
        }

        $this->loginQueue[$playerName]['stage'] = 'handshake';
        $this->logPacket("Handshake completed", $session, 'info');

        $this->sendServerHandshake($session);
    }

    private function sendServerHandshake(PlayerSession $session): void {
        $packet = new ServerToClientHandshakePacket();
        $packet->jwt = $this->generateJWT($session);

        $session->sendPacket($packet);
        $this->logPacket("Sent ServerHandshake", $session);
    }

    private function generateJWT(PlayerSession $session): string {
        $header = base64_encode(json_encode(['alg' => 'none', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'exp' => time() + 3600,
            'iat' => time(),
            'nbf' => time()
        ]));

        return "{$header}.{$payload}.";
    }

    private function handleResourcePackResponse(DataPacket $packet, PlayerSession $session): void {
        $player = $session->getPlayer();
        $playerName = $player->getName();

        if (!isset($this->loginQueue[$playerName])) {
            return;
        }

        $status = $packet->status;
        $this->resourcePackResponses[$playerName] = $status;

        $this->logPacket("ResourcePack response: {$status}", $session);

        if ($status === ResourcePackClientResponsePacket::STATUS_HAVE_ALL_PACKS) {
            $this->sendResourcePackStack($session);
        } elseif ($status === ResourcePackClientResponsePacket::STATUS_COMPLETED) {
            $this->completeLogin($session);
        }
    }

    private function sendResourcePackStack(PlayerSession $session): void {
        $packet = new ResourcePackStackPacket();
        $packet->resourcePackStack = [];
        $packet->behaviorPackStack = [];
        $packet->isExperimental = false;
        $packet->baseGameVersion = $session->getProtocolVersion();

        $session->sendPacket($packet);
        $this->logPacket("Sent ResourcePackStack", $session);
    }

    private function completeLogin(PlayerSession $session): void {
        $player = $session->getPlayer();
        $playerName = $player->getName();

        if (!isset($this->loginQueue[$playerName])) {
            return;
        }

        $this->loginQueue[$playerName]['stage'] = 'complete';
        $this->sendStartGame($session);
        $this->sendBiomeDefinitions($session);
        $this->sendCreativeContent($session);

        unset($this->loginQueue[$playerName]);

        $this->logPacket("Login completed successfully", $session, 'info');
        $this->plugin->getServerManager()->incrementTotalConnections();
    }

    private function sendStartGame(PlayerSession $session): void {
        $player = $session->getPlayer();
        $world = $player->getWorld();

        $packet = new StartGamePacket();
        $packet->entityUniqueId = $player->getId();
        $packet->entityRuntimeId = $player->getId();
        $packet->playerGamemode = $player->getGamemode()->id();
        $packet->playerPosition = $player->getPosition()->asVector3();
        $packet->pitch = $player->getLocation()->getPitch();
        $packet->yaw = $player->getLocation()->getYaw();
        $packet->seed = 0;
        $packet->spawnSettings = new SpawnSettings(
            SpawnSettings::BIOME_TYPE_DEFAULT,
            "",
            DimensionIds::OVERWORLD
        );
        $packet->generator = 1;
        $packet->worldGamemode = $world->getWorldManager()->getDefaultGamemode()->id();
        $packet->difficulty = $world->getDifficulty();
        $packet->spawnPosition = $world->getSpawnLocation()->asVector3();
        $packet->hasAchievementsDisabled = true;
        $packet->time = $world->getTime();
        $packet->eduEditionOffer = 0;
        $packet->rainLevel = 0.0;
        $packet->lightningLevel = 0.0;
        $packet->commandsEnabled = true;
        $packet->levelId = "";
        $packet->worldName = $world->getDisplayName();

        $session->sendPacket($packet);
        $this->logPacket("Sent StartGame", $session);
    }

    private function sendBiomeDefinitions(PlayerSession $session): void {
        $packet = new BiomeDefinitionListPacket();
        $packet->namedtag = $this->getBiomeDefinitions($session->getProtocol());

        $session->sendPacket($packet);
        $this->logPacket("Sent BiomeDefinitions", $session);
    }

    private function getBiomeDefinitions(int $protocol): \pocketmine\nbt\tag\CompoundTag {
        $nbt = new \pocketmine\nbt\tag\CompoundTag();
        return $nbt;
    }

    private function sendCreativeContent(PlayerSession $session): void {
        $packet = new CreativeContentPacket();
        $packet->entries = $this->getCreativeItems($session->getProtocol());

        $session->sendPacket($packet);
        $this->logPacket("Sent CreativeContent", $session);
    }

    private function getCreativeItems(int $protocol): array {
        return [];
    }

    private function disconnectPlayer(\pocketmine\player\Player $player, string $reason): void {
        $packet = new DisconnectPacket();
        $packet->message = $reason;
        $packet->hideDisconnectionScreen = false;

        $player->getNetworkSession()->sendDataPacket($packet);
        $player->kick($reason);

        $this->plugin->getMVLogger()->info("Player {$player->getName()} disconnected: {$reason}");
    }

    public function getLoginQueue(): array {
        return $this->loginQueue;
    }

    public function clearLoginQueue(): void {
        $this->loginQueue = [];
        $this->resourcePackResponses = [];
    }

    public function isPlayerInQueue(string $playerName): bool {
        return isset($this->loginQueue[$playerName]);
    }

    public function getLoginStage(string $playerName): ?string {
        return $this->loginQueue[$playerName]['stage'] ?? null;
    }
}