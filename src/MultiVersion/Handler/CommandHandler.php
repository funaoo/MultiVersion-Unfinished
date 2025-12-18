<?php

declare(strict_types=1);

namespace MultiVersion\Handler;

use MultiVersion\MultiVersion;
use MultiVersion\Network\PacketRegistry;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class CommandHandler extends PacketHandler{

    protected function initialize(): void{}

    public function register(PacketRegistry $registry): void{}

    public function registerMultiVersionCommands(): void{
        $this->registerMVCommand();
        $this->registerProtocolCommand();
        $this->registerVersionCommand();
    }

    private function registerMVCommand(): void{
        $command = new class extends Command{

            private MultiVersion $plugin;

            public function __construct(){
                parent::__construct(
                    "multiversion",
                    "MultiVersion main command",
                    "/multiversion <info|stats|reload>",
                    ["mv"]
                );
                $this->plugin = MultiVersion::getInstance();
                $this->setPermission("multiversion.command");
            }

            public function execute(CommandSender $sender, string $label, array $args): bool{
                if(!$this->testPermission($sender)){
                    return true;
                }

                $sub = $args[0] ?? "info";

                match($sub){
                    "info" => $sender->sendMessage("§aMultiVersion §7v1.0.0"),
                    "stats" => $sender->sendMessage(
                        "§7Active sessions: " . $this->plugin->getVersionRegistry()->getActiveSessionCount()
                    ),
                    "reload" => $this->reload($sender),
                    default => $sender->sendMessage("§cUnknown subcommand")
                };

                return true;
            }

            private function reload(CommandSender $sender): void{
                $this->plugin->getMVConfig()->reload();
                $sender->sendMessage("§aConfig reloaded");
            }
        };

        MultiVersion::getInstance()?->getServer()->getCommandMap()->register("multiversion", $command);
    }

    private function registerProtocolCommand(): void{
        $command = new class extends Command{

            private MultiVersion $plugin;

            public function __construct(){
                parent::__construct(
                    "protocol",
                    "Show your protocol",
                    "/protocol",
                    ["proto"]
                );
                $this->plugin = MultiVersion::getInstance();
                $this->setPermission("multiversion.command");
            }

            public function execute(CommandSender $sender, string $label, array $args): bool{
                if(!$this->testPermission($sender)){
                    return true;
                }

                if(!$sender instanceof Player){
                    $sender->sendMessage("§cThis command can only be used in-game");
                    return true;
                }

                $session = $this->plugin->getNetworkManager()->getSession($sender);
                if($session === null){
                    $sender->sendMessage("§cSession not found");
                    return true;
                }

                $sender->sendMessage("§aProtocol: §e" . $session->getProtocol());
                return true;
            }
        };

        MultiVersion::getInstance()?->getServer()->getCommandMap()->register("protocol", $command);
    }

    private function registerVersionCommand(): void{
        $command = new class extends Command{

            public function __construct(){
                parent::__construct(
                    "mvversion",
                    "MultiVersion version",
                    "/mvversion",
                    ["ver"]
                );
                $this->setPermission("multiversion.command");
            }

            public function execute(CommandSender $sender, string $label, array $args): bool{
                if(!$this->testPermission($sender)){
                    return true;
                }

                $sender->sendMessage("§aMultiVersion §7v1.0.0");
                return true;
            }
        };

        MultiVersion::getInstance()?->getServer()->getCommandMap()->register("mvversion", $command);
    }
}
