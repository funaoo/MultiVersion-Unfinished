<?php
declare(strict_types=1);

namespace MultiVersion\Network;

use MultiVersion\MultiVersion;
use MultiVersion\Utils\VersionComparator;
use pocketmine\player\Player;

final class VersionDetector{

    private MultiVersion $plugin;
    private int $defaultProtocol;

    public function __construct(MultiVersion $plugin){
        $this->plugin = $plugin;
        $this->defaultProtocol = $plugin->getMVConfig()->getDefaultProtocol();
    }

    public function detect(Player $player): int{
        $protocol = $this->detectFromPlayerInfo($player);

        if($protocol !== null && $this->isProtocolSupported($protocol)){
            return $protocol;
        }

        $this->plugin->getMVLogger()->warning(
            "Could not detect protocol for {$player->getName()}, using default {$this->defaultProtocol}"
        );

        return $this->defaultProtocol;
    }

    private function detectFromPlayerInfo(Player $player): ?int{
        try{
            $info = $player->getPlayerInfo();
            $extra = $info->getExtraData();

            if(!is_array($extra)){
                return null;
            }

            if(isset($extra["GameVersion"]) && is_string($extra["GameVersion"])){
                $version = $extra["GameVersion"];

                $protocol = VersionComparator::versionToProtocol($version);
                if($protocol !== null){
                    return $protocol;
                }

                return $this->guessProtocolFromVersion($version);
            }
        }catch(\Throwable $e){
            $this->plugin->getMVLogger()->error(
                "Protocol detection failed for {$player->getName()}: {$e->getMessage()}"
            );
        }

        return null;
    }

    private function guessProtocolFromVersion(string $version): ?int{
        $parts = explode(".", $version);
        if(count($parts) < 2){
            return null;
        }

        $major = (int)$parts[0];
        $minor = (int)$parts[1];

        if($major !== 1){
            return null;
        }

        return match(true){
            $minor >= 21 => 621,
            $minor >= 20 => 594,
            $minor >= 18 => 527,
            default => null
        };
    }

    private function isProtocolSupported(int $protocol): bool{
        return $this->plugin->getVersionRegistry()->isProtocolSupported($protocol);
    }

    public function detectFromPacket(object $packet): ?int{
        if(property_exists($packet, "protocol") && is_int($packet->protocol)){
            return $packet->protocol;
        }

        return null;
    }

    public function setDefaultProtocol(int $protocol): void{
        if($this->isProtocolSupported($protocol)){
            $this->defaultProtocol = $protocol;
        }
    }

    public function getDefaultProtocol(): int{
        return $this->defaultProtocol;
    }

    public function getSupportedProtocols(): array{
        return $this->plugin->getVersionRegistry()->getSupportedProtocols();
    }

    public function getClosestSupportedProtocol(int $protocol): int{
        $supported = $this->getSupportedProtocols();

        if(in_array($protocol, $supported, true)){
            return $protocol;
        }

        $closest = $supported[0];
        $minDiff = abs($protocol - $closest);

        foreach($supported as $supportedProtocol){
            $diff = abs($protocol - $supportedProtocol);
            if($diff < $minDiff){
                $minDiff = $diff;
                $closest = $supportedProtocol;
            }
        }

        return $closest;
    }
}
