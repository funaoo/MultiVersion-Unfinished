<?php

declare(strict_types=1);

namespace MultiVersion;

use MultiVersion\Core\EventDispatcher;
use MultiVersion\Core\PacketRouter;
use MultiVersion\Core\ServerManager;
use MultiVersion\Core\VersionRegistry;
use MultiVersion\Handler\ChunkHandler;
use MultiVersion\Handler\CommandHandler;
use MultiVersion\Handler\GameHandler;
use MultiVersion\Handler\InventoryHandler;
use MultiVersion\Handler\LoginHandler;
use MultiVersion\Network\NetworkManager;
use MultiVersion\Network\PacketRegistry;
use MultiVersion\Utils\Config;
use MultiVersion\Utils\Logger;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\plugin\PluginBase;

final class MultiVersion extends PluginBase implements Listener{

    private static ?MultiVersion $instance = null;

    private Config $mvConfig;
    private Logger $mvLogger;

    private VersionRegistry $versionRegistry;
    private PacketRouter $packetRouter;
    private ServerManager $serverManager;
    private EventDispatcher $eventDispatcher;

    private PacketRegistry $packetRegistry;
    private NetworkManager $networkManager;

    private LoginHandler $loginHandler;
    private GameHandler $gameHandler;
    private InventoryHandler $inventoryHandler;
    private ChunkHandler $chunkHandler;
    private CommandHandler $commandHandler;

    public static function getInstance(): ?MultiVersion{
        return self::$instance;
    }

    public function onLoad(): void{
        self::$instance = $this;
    }

    public function onEnable(): void{
        $pm = PermissionManager::getInstance();

        if($pm->getPermission("multiversion.command") === null){
            $pm->addPermission(new Permission(
                "multiversion.command",
                "Use MultiVersion commands"
            ));
        }

        $this->initializeConfig();
        $this->initializeLogger();
        $this->initializeCore();
        $this->initializeNetwork();
        $this->initializeHandlers();
        $this->registerEvents();
        $this->registerCommands();

        $this->mvLogger->info("MultiVersion plugin enabled!");
        $this->mvLogger->info("Supported protocols: 621 (1.21.130), 594 (1.20.40), 527 (1.18.12)");
    }

    public function onDisable(): void{
        if(isset($this->serverManager)){
            $this->serverManager->saveStatistics();
        }
        if(isset($this->mvLogger)){
            $this->mvLogger->info("MultiVersion plugin disabled!");
        }
    }

    private function initializeConfig(): void{
        $this->saveDefaultConfig();
        $this->mvConfig = new Config($this->getDataFolder());
    }

    private function initializeLogger(): void{
        $logPath = $this->getDataFolder() . "logs/multiversion.log";
        $this->mvLogger = new Logger($logPath, $this->mvConfig->getLogLevel());
    }

    private function initializeCore(): void{
        $this->versionRegistry = new VersionRegistry($this);
        $this->packetRouter = new PacketRouter($this);
        $this->serverManager = new ServerManager($this);
        $this->eventDispatcher = new EventDispatcher($this);
    }

    private function initializeNetwork(): void{
        $this->packetRegistry = new PacketRegistry($this);
        $this->networkManager = new NetworkManager($this);
    }

    private function initializeHandlers(): void{
        $this->loginHandler = new LoginHandler($this);
        $this->gameHandler = new GameHandler($this);
        $this->inventoryHandler = new InventoryHandler($this);
        $this->chunkHandler = new ChunkHandler($this);
        $this->commandHandler = new CommandHandler($this);

        $this->loginHandler->register($this->packetRegistry);
        $this->gameHandler->register($this->packetRegistry);
        $this->inventoryHandler->register($this->packetRegistry);
        $this->chunkHandler->register($this->packetRegistry);
        $this->commandHandler->register($this->packetRegistry);
    }

    private function registerEvents(): void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getPluginManager()->registerEvents($this->networkManager, $this);
    }

    private function registerCommands(): void{
        $this->commandHandler->registerMultiVersionCommands();
    }

    public function getMVConfig(): Config{
        return $this->mvConfig;
    }

    public function getMVLogger(): Logger{
        return $this->mvLogger;
    }

    public function getVersionRegistry(): VersionRegistry{
        return $this->versionRegistry;
    }

    public function getPacketRouter(): PacketRouter{
        return $this->packetRouter;
    }

    public function getServerManager(): ServerManager{
        return $this->serverManager;
    }

    public function getEventDispatcher(): EventDispatcher{
        return $this->eventDispatcher;
    }

    public function getNetworkManager(): NetworkManager{
        return $this->networkManager;
    }

    public function getPacketRegistry(): PacketRegistry{
        return $this->packetRegistry;
    }

    public function getLoginHandler(): LoginHandler{
        return $this->loginHandler;
    }

    public function getGameHandler(): GameHandler{
        return $this->gameHandler;
    }

    public function getInventoryHandler(): InventoryHandler{
        return $this->inventoryHandler;
    }

    public function getChunkHandler(): ChunkHandler{
        return $this->chunkHandler;
    }

    public function getCommandHandler(): CommandHandler{
        return $this->commandHandler;
    }

    public function getItemTranslator(): object{
        return new class{
            public function translate(Item $item, int $protocol): Item{
                return $item;
            }
        };
    }
}
