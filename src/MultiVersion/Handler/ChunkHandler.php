<?php

declare(strict_types=1);

namespace MultiVersion\Handler;

use MultiVersion\MultiVersion;
use MultiVersion\Network\PacketRegistry;
use MultiVersion\Network\PlayerSession;
use pocketmine\network\mcpe\protocol\ChunkRadiusUpdatedPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;

final class ChunkHandler extends PacketHandler {

    private array $requestedChunks = [];
    private array $sentChunks = [];
    private array $chunkCache = [];
    private array $playerViewDistances = [];
    private int $maxViewDistance = 16;
    private int $minViewDistance = 4;

    protected function initialize(): void {
        $this->handledPackets = [
            RequestChunkRadiusPacket::class,
            NetworkChunkPublisherUpdatePacket::class
        ];
    }

    public function register(PacketRegistry $registry): void {
        $this->registerPacket($registry, RequestChunkRadiusPacket::class,
            fn($packet, $session) => $this->handleRequestChunkRadius($packet, $session), 10);

        $this->registerPacket($registry, NetworkChunkPublisherUpdatePacket::class,
            fn($packet, $session) => $this->handleChunkPublisherUpdate($packet, $session), 5);
    }

    private function handleRequestChunkRadius(RequestChunkRadiusPacket $packet, PlayerSession $session): void {
        $player = $session->getPlayer();
        $playerName = $player->getName();
        $requestedRadius = $packet->radius;

        $acceptedRadius = $this->clampViewDistance($requestedRadius);
        $this->playerViewDistances[$playerName] = $acceptedRadius;

        $this->logPacket("Chunk radius requested: {$requestedRadius}, accepted: {$acceptedRadius}", $session, 'info');

        $this->sendChunkRadiusUpdate($session, $acceptedRadius);
        $this->sendInitialChunks($session, $acceptedRadius);
    }

    private function clampViewDistance(int $radius): int {
        return max($this->minViewDistance, min($this->maxViewDistance, $radius));
    }

    private function sendChunkRadiusUpdate(PlayerSession $session, int $radius): void {
        $packet = new ChunkRadiusUpdatedPacket();
        $packet->radius = $radius;

        $session->sendPacket($packet);
        $this->logPacket("Chunk radius updated: {$radius}", $session);
    }

    private function sendInitialChunks(PlayerSession $session, int $radius): void {
        $player = $session->getPlayer();
        $playerName = $player->getName();
        $world = $player->getWorld();
        $playerPos = $player->getPosition();

        $centerChunkX = $playerPos->getFloorX() >> 4;
        $centerChunkZ = $playerPos->getFloorZ() >> 4;

        $chunksToSend = [];

        for ($x = -$radius; $x <= $radius; $x++) {
            for ($z = -$radius; $z <= $radius; $z++) {
                $chunkX = $centerChunkX + $x;
                $chunkZ = $centerChunkZ + $z;

                if ($this->isChunkInRadius($x, $z, $radius)) {
                    $chunksToSend[] = ['x' => $chunkX, 'z' => $chunkZ];
                }
            }
        }

        $this->logPacket("Sending {$chunksToSend} initial chunks", $session, 'info');

        foreach ($chunksToSend as $chunkCoord) {
            $this->sendChunk($session, $world, $chunkCoord['x'], $chunkCoord['z']);
        }
    }

    private function isChunkInRadius(int $offsetX, int $offsetZ, int $radius): bool {
        $distance = sqrt($offsetX * $offsetX + $offsetZ * $offsetZ);
        return $distance <= $radius;
    }

    public function sendChunk(PlayerSession $session, World $world, int $chunkX, int $chunkZ): void {
        $player = $session->getPlayer();
        $playerName = $player->getName();
        $protocol = $session->getProtocol();

        $chunkHash = World::chunkHash($chunkX, $chunkZ);

        if (isset($this->sentChunks[$playerName][$chunkHash])) {
            return;
        }

        $chunk = $world->getChunk($chunkX, $chunkZ);

        if ($chunk === null) {
            $this->requestChunkLoad($world, $chunkX, $chunkZ, $session);
            return;
        }

        $serialized = $this->serializeChunk($chunk, $protocol);

        $packet = new LevelChunkPacket();
        $packet->chunkX = $chunkX;
        $packet->chunkZ = $chunkZ;
        $packet->subChunkCount = $this->getSubChunkCount($chunk);
        $packet->data = $serialized;

        $session->sendPacket($packet);

        if (!isset($this->sentChunks[$playerName])) {
            $this->sentChunks[$playerName] = [];
        }

        $this->sentChunks[$playerName][$chunkHash] = microtime(true);

        $this->logPacket("Sent chunk ({$chunkX}, {$chunkZ})", $session);
    }

    private function requestChunkLoad(World $world, int $chunkX, int $chunkZ, PlayerSession $session): void {
        $playerName = $session->getPlayer()->getName();

        if (!isset($this->requestedChunks[$playerName])) {
            $this->requestedChunks[$playerName] = [];
        }

        $chunkHash = World::chunkHash($chunkX, $chunkZ);
        $this->requestedChunks[$playerName][$chunkHash] = [
            'x' => $chunkX,
            'z' => $chunkZ,
            'time' => microtime(true)
        ];

        $world->loadChunk($chunkX, $chunkZ);
        $this->logPacket("Requested chunk load ({$chunkX}, {$chunkZ})", $session);
    }

    private function serializeChunk(Chunk $chunk, int $protocol): string {
        $cacheKey = $this->getChunkCacheKey($chunk, $protocol);

        if (isset($this->chunkCache[$cacheKey])) {
            return $this->chunkCache[$cacheKey];
        }

        $serialized = $this->performChunkSerialization($chunk, $protocol);

        $this->chunkCache[$cacheKey] = $serialized;

        if (count($this->chunkCache) > 1000) {
            $this->cleanChunkCache();
        }

        return $serialized;
    }

    private function performChunkSerialization(Chunk $chunk, int $protocol): string {
        $stream = new \pocketmine\utils\BinaryStream();

        $subChunkCount = $this->getSubChunkCount($chunk);

        for ($y = 0; $y < $subChunkCount; $y++) {
            $subChunk = $chunk->getSubChunk($y);
            $this->serializeSubChunk($stream, $subChunk, $protocol);
        }

        $this->serializeBiomes($stream, $chunk, $protocol);

        $stream->putByte(0);

        return $stream->getBuffer();
    }

    private function serializeSubChunk(\pocketmine\utils\BinaryStream $stream, object $subChunk, int $protocol): void {
        $stream->putByte(8);
        $stream->putByte(1);

        $paletteSize = 1;
        $stream->putLInt($paletteSize);
        $stream->putLInt(0);

        for ($i = 0; $i < 4096; $i++) {
            $stream->putByte(0);
        }
    }

    private function serializeBiomes(\pocketmine\utils\BinaryStream $stream, Chunk $chunk, int $protocol): void {
        for ($i = 0; $i < 256; $i++) {
            $stream->putByte(1);
        }
    }

    private function getSubChunkCount(Chunk $chunk): int {
        return $chunk->getHeight() >> 4;
    }

    private function getChunkCacheKey(Chunk $chunk, int $protocol): string {
        return $chunk->getX() . ":" . $chunk->getZ() . ":" . $protocol;
    }

    private function cleanChunkCache(): void {
        $this->chunkCache = array_slice($this->chunkCache, -500, 500, true);
    }

    private function handleChunkPublisherUpdate(NetworkChunkPublisherUpdatePacket $packet, PlayerSession $session): void {
        $player = $session->getPlayer();
        $radius = $packet->radius;

        $this->logPacket("Chunk publisher update - Radius: {$radius}", $session);
    }

    public function unloadChunkForPlayer(PlayerSession $session, int $chunkX, int $chunkZ): void {
        $playerName = $session->getPlayer()->getName();
        $chunkHash = World::chunkHash($chunkX, $chunkZ);

        if (isset($this->sentChunks[$playerName][$chunkHash])) {
            unset($this->sentChunks[$playerName][$chunkHash]);
            $this->logPacket("Unloaded chunk ({$chunkX}, {$chunkZ})", $session);
        }
    }

    public function updatePlayerChunks(PlayerSession $session): void {
        $player = $session->getPlayer();
        $playerName = $player->getName();
        $world = $player->getWorld();
        $playerPos = $player->getPosition();

        $viewDistance = $this->playerViewDistances[$playerName] ?? $this->maxViewDistance;

        $centerChunkX = $playerPos->getFloorX() >> 4;
        $centerChunkZ = $playerPos->getFloorZ() >> 4;

        $requiredChunks = [];

        for ($x = -$viewDistance; $x <= $viewDistance; $x++) {
            for ($z = -$viewDistance; $z <= $viewDistance; $z++) {
                if ($this->isChunkInRadius($x, $z, $viewDistance)) {
                    $chunkX = $centerChunkX + $x;
                    $chunkZ = $centerChunkZ + $z;
                    $requiredChunks[World::chunkHash($chunkX, $chunkZ)] = ['x' => $chunkX, 'z' => $chunkZ];
                }
            }
        }

        if (!isset($this->sentChunks[$playerName])) {
            $this->sentChunks[$playerName] = [];
        }

        foreach ($this->sentChunks[$playerName] as $chunkHash => $time) {
            if (!isset($requiredChunks[$chunkHash])) {
                World::getXZ($chunkHash, $chunkX, $chunkZ);
                $this->unloadChunkForPlayer($session, $chunkX, $chunkZ);
            }
        }

        foreach ($requiredChunks as $chunkHash => $coords) {
            if (!isset($this->sentChunks[$playerName][$chunkHash])) {
                $this->sendChunk($session, $world, $coords['x'], $coords['z']);
            }
        }
    }

    public function clearPlayerData(string $playerName): void {
        unset($this->sentChunks[$playerName]);
        unset($this->requestedChunks[$playerName]);
        unset($this->playerViewDistances[$playerName]);
    }

    public function getSentChunks(string $playerName): array {
        return $this->sentChunks[$playerName] ?? [];
    }

    public function getPlayerViewDistance(string $playerName): int {
        return $this->playerViewDistances[$playerName] ?? $this->maxViewDistance;
    }

    public function setMaxViewDistance(int $distance): void {
        $this->maxViewDistance = max(4, min(32, $distance));
    }

    public function setMinViewDistance(int $distance): void {
        $this->minViewDistance = max(2, min(16, $distance));
    }

    public function getCacheStatistics(): array {
        return [
            'cached_chunks' => count($this->chunkCache),
            'total_sent_chunks' => array_sum(array_map('count', $this->sentChunks)),
            'pending_requests' => array_sum(array_map('count', $this->requestedChunks))
        ];
    }

    public function clearCache(): void {
        $this->chunkCache = [];
    }
}