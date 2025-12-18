<?php
declare(strict_types=1);

namespace MultiVersion\Network;

use MultiVersion\MultiVersion;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\raklib\RakLibInterface;
use pocketmine\network\PacketHandlingException;
use pocketmine\Server;

final class RakNetInterface {

    private MultiVersion $plugin;
    private Server $server;
    private array $sessionData = [];
    private array $connectionAttempts = [];
    private int $maxConnectionAttempts = 5;
    private int $connectionTimeout = 30;

    public function __construct(MultiVersion $plugin) {
        $this->plugin = $plugin;
        $this->server = $plugin->getServer();
    }

    public function onClientConnect(string $address, int $port, int $clientId): void {
        $identifier = $this->getConnectionIdentifier($address, $port);

        if (!isset($this->connectionAttempts[$identifier])) {
            $this->connectionAttempts[$identifier] = [
                'count' => 0,
                'first_attempt' => microtime(true),
                'last_attempt' => microtime(true)
            ];
        }

        $attempt = &$this->connectionAttempts[$identifier];
        $attempt['count']++;
        $attempt['last_attempt'] = microtime(true);

        if ($attempt['count'] > $this->maxConnectionAttempts) {
            $elapsed = microtime(true) - $attempt['first_attempt'];

            if ($elapsed < $this->connectionTimeout) {
                $this->plugin->getMVLogger()->warning(
                    "Too many connection attempts from {$address}:{$port} ({$attempt['count']} attempts)"
                );
                return;
            } else {
                $attempt['count'] = 1;
                $attempt['first_attempt'] = microtime(true);
            }
        }

        $this->sessionData[$clientId] = [
            'address' => $address,
            'port' => $port,
            'connected_at' => microtime(true),
            'protocol' => null,
            'authenticated' => false
        ];

        $this->plugin->getMVLogger()->debug(
            "Client connected: {$address}:{$port} (ID: {$clientId})"
        );
    }

    public function onClientDisconnect(int $clientId, string $reason = "Unknown"): void {
        if (!isset($this->sessionData[$clientId])) {
            return;
        }

        $session = $this->sessionData[$clientId];
        $duration = microtime(true) - $session['connected_at'];

        $this->plugin->getMVLogger()->debug(
            "Client disconnected: {$session['address']}:{$session['port']} " .
            "(ID: {$clientId}, Duration: " . round($duration, 2) . "s, Reason: {$reason})"
        );

        unset($this->sessionData[$clientId]);
    }

    public function onPacketReceive(int $clientId, string $buffer): void {
        if (!isset($this->sessionData[$clientId])) {
            $this->plugin->getMVLogger()->warning(
                "Received packet from unknown client ID: {$clientId}"
            );
            return;
        }

        try {
            $this->processPacket($clientId, $buffer);
        } catch (\Exception $e) {
            $this->plugin->getMVLogger()->error(
                "Error processing packet from client {$clientId}: {$e->getMessage()}"
            );
            $this->handlePacketError($clientId, $e);
        }
    }

    private function processPacket(int $clientId, string $buffer): void {
        $session = &$this->sessionData[$clientId];

        if ($session['protocol'] === null) {
            $protocol = $this->detectProtocolFromBuffer($buffer);

            if ($protocol !== null) {
                $session['protocol'] = $protocol;

                $this->plugin->getMVLogger()->info(
                    "Protocol detected for client {$clientId}: {$protocol}"
                );
            }
        }
    }

    private function detectProtocolFromBuffer(string $buffer): ?int {
        try {
            $stream = new \MultiVersion\Utils\BinaryStream($buffer);

            if (strlen($buffer) < 1) {
                return null;
            }

            $packetId = $stream->getByte();

            if ($packetId === 0x01) {
                if (strlen($buffer) < 5) {
                    return null;
                }

                $stream->getInt();

                if (!$stream->feof()) {
                    $protocol = $stream->getInt();

                    if ($this->isValidProtocol($protocol)) {
                        return $protocol;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->plugin->getMVLogger()->debug(
                "Could not detect protocol from buffer: {$e->getMessage()}"
            );
        }

        return null;
    }

    private function isValidProtocol(int $protocol): bool {
        return $this->plugin->getVersionRegistry()->isProtocolSupported($protocol);
    }

    private function handlePacketError(int $clientId, \Exception $e): void {
        $session = $this->sessionData[$clientId] ?? null;

        if ($session === null) {
            return;
        }

        if ($e instanceof PacketHandlingException) {
            $this->plugin->getMVLogger()->warning(
                "Packet handling error for client {$clientId}: {$e->getMessage()}"
            );
        }
    }

    public function onPacketSend(int $clientId, string $buffer): void {
        if (!isset($this->sessionData[$clientId])) {
            return;
        }
    }

    public function onRawPacketReceive(string $address, int $port, string $buffer): void {
        $identifier = $this->getConnectionIdentifier($address, $port);

        $this->plugin->getMVLogger()->debug(
            "Raw packet received from {$address}:{$port} (" . strlen($buffer) . " bytes)"
        );
    }

    private function getConnectionIdentifier(string $address, int $port): string {
        return "{$address}:{$port}";
    }

    public function setProtocol(int $clientId, int $protocol): void {
        if (!isset($this->sessionData[$clientId])) {
            return;
        }

        if (!$this->isValidProtocol($protocol)) {
            $this->plugin->getMVLogger()->warning(
                "Attempted to set invalid protocol {$protocol} for client {$clientId}"
            );
            return;
        }

        $this->sessionData[$clientId]['protocol'] = $protocol;

        $this->plugin->getMVLogger()->info(
            "Protocol set for client {$clientId}: {$protocol}"
        );
    }

    public function getProtocol(int $clientId): ?int {
        return $this->sessionData[$clientId]['protocol'] ?? null;
    }

    public function isAuthenticated(int $clientId): bool {
        return $this->sessionData[$clientId]['authenticated'] ?? false;
    }

    public function setAuthenticated(int $clientId, bool $authenticated): void {
        if (!isset($this->sessionData[$clientId])) {
            return;
        }

        $this->sessionData[$clientId]['authenticated'] = $authenticated;

        if ($authenticated) {
            $this->plugin->getMVLogger()->info("Client {$clientId} authenticated successfully");
        }
    }

    public function getSessionData(int $clientId): ?array {
        return $this->sessionData[$clientId] ?? null;
    }

    public function hasSession(int $clientId): bool {
        return isset($this->sessionData[$clientId]);
    }

    public function getActiveSessions(): array {
        return $this->sessionData;
    }

    public function getActiveSessionCount(): int {
        return count($this->sessionData);
    }

    public function getSessionsByProtocol(int $protocol): array {
        return array_filter(
            $this->sessionData,
            fn($session) => ($session['protocol'] ?? null) === $protocol
        );
    }

    public function closeSession(int $clientId, string $reason = "Disconnected"): void {
        if (!isset($this->sessionData[$clientId])) {
            return;
        }

        $this->onClientDisconnect($clientId, $reason);
    }

    public function setMaxConnectionAttempts(int $max): void {
        $this->maxConnectionAttempts = max(1, $max);
    }

    public function getMaxConnectionAttempts(): int {
        return $this->maxConnectionAttempts;
    }

    public function setConnectionTimeout(int $seconds): void {
        $this->connectionTimeout = max(1, $seconds);
    }

    public function getConnectionTimeout(): int {
        return $this->connectionTimeout;
    }

    public function clearConnectionAttempts(string $address = null): void {
        if ($address === null) {
            $this->connectionAttempts = [];
            $this->plugin->getMVLogger()->info("Cleared all connection attempts");
        } else {
            foreach ($this->connectionAttempts as $identifier => $data) {
                if (str_starts_with($identifier, $address . ':')) {
                    unset($this->connectionAttempts[$identifier]);
                }
            }
            $this->plugin->getMVLogger()->info("Cleared connection attempts for {$address}");
        }
    }

    public function getConnectionAttempts(string $address = null): array {
        if ($address === null) {
            return $this->connectionAttempts;
        }

        $filtered = [];
        foreach ($this->connectionAttempts as $identifier => $data) {
            if (str_starts_with($identifier, $address . ':')) {
                $filtered[$identifier] = $data;
            }
        }

        return $filtered;
    }

    public function banAddress(string $address, int $duration = 3600): void {
        $this->plugin->getMVLogger()->warning("Address {$address} banned for {$duration} seconds");
    }

    public function getStatistics(): array {
        $protocolCounts = [];
        foreach ($this->sessionData as $session) {
            $protocol = $session['protocol'] ?? 'unknown';
            $protocolCounts[$protocol] = ($protocolCounts[$protocol] ?? 0) + 1;
        }

        return [
            'active_sessions' => $this->getActiveSessionCount(),
            'total_connection_attempts' => array_sum(array_column($this->connectionAttempts, 'count')),
            'unique_addresses' => count($this->connectionAttempts),
            'protocol_distribution' => $protocolCounts,
            'authenticated_sessions' => count(array_filter($this->sessionData, fn($s) => $s['authenticated']))
        ];
    }

    public function cleanup(): void {
        $now = microtime(true);
        $timeout = 300;

        foreach ($this->sessionData as $clientId => $session) {
            $elapsed = $now - $session['connected_at'];

            if ($elapsed > $timeout && !$session['authenticated']) {
                $this->plugin->getMVLogger()->info(
                    "Cleaning up stale session {$clientId} (inactive for " . round($elapsed, 2) . "s)"
                );
                $this->closeSession($clientId, "Session timeout");
            }
        }

        foreach ($this->connectionAttempts as $identifier => $attempt) {
            $elapsed = $now - $attempt['last_attempt'];

            if ($elapsed > $this->connectionTimeout * 2) {
                unset($this->connectionAttempts[$identifier]);
            }
        }
    }

    public function getAverageSessionDuration(): float {
        if (empty($this->sessionData)) {
            return 0.0;
        }

        $now = microtime(true);
        $total = 0.0;

        foreach ($this->sessionData as $session) {
            $total += $now - $session['connected_at'];
        }

        return $total / count($this->sessionData);
    }

    public function getOldestSession(): ?array {
        if (empty($this->sessionData)) {
            return null;
        }

        $oldest = null;
        $oldestTime = PHP_FLOAT_MAX;

        foreach ($this->sessionData as $clientId => $session) {
            if ($session['connected_at'] < $oldestTime) {
                $oldestTime = $session['connected_at'];
                $oldest = array_merge($session, ['client_id' => $clientId]);
            }
        }

        return $oldest;
    }

    public function getNewestSession(): ?array {
        if (empty($this->sessionData)) {
            return null;
        }

        $newest = null;
        $newestTime = 0.0;

        foreach ($this->sessionData as $clientId => $session) {
            if ($session['connected_at'] > $newestTime) {
                $newestTime = $session['connected_at'];
                $newest = array_merge($session, ['client_id' => $clientId]);
            }
        }

        return $newest;
    }
}