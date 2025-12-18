<?php

declare(strict_types=1);

namespace MultiVersion\Protocol\Packets;

use MultiVersion\Protocol\Packets\BasePacket;

final class PlayStatusPacket extends BasePacket {

    public const LOGIN_SUCCESS = 0;
    public const LOGIN_FAILED_CLIENT = 1;
    public const LOGIN_FAILED_SERVER = 2;
    public const PLAYER_SPAWN = 3;
    public const LOGIN_FAILED_INVALID_TENANT = 4;
    public const LOGIN_FAILED_VANILLA_EDU = 5;
    public const LOGIN_FAILED_EDU_VANILLA = 6;
    public const LOGIN_FAILED_SERVER_FULL = 7;

    public int $status;

    public function __construct() {
        $this->packetId = 0x02;
    }

    public function canBeSentBeforeLogin(): bool {
        return true;
    }

    public function encode(): void {
        $stream = $this->getBuffer();
        $this->encodeHeader($stream);

        $stream->putInt($this->status);

        $this->encoded = true;
    }

    public function decode(): void {
        $stream = $this->getBuffer();
        $this->decodeHeader($stream);

        $this->status = $stream->getInt();

        $this->decoded = true;
    }

    public function isSuccess(): bool {
        return $this->status === self::LOGIN_SUCCESS;
    }

    public function isSpawn(): bool {
        return $this->status === self::PLAYER_SPAWN;
    }

    public function isFailed(): bool {
        return in_array($this->status, [
            self::LOGIN_FAILED_CLIENT,
            self::LOGIN_FAILED_SERVER,
            self::LOGIN_FAILED_INVALID_TENANT,
            self::LOGIN_FAILED_VANILLA_EDU,
            self::LOGIN_FAILED_EDU_VANILLA,
            self::LOGIN_FAILED_SERVER_FULL
        ], true);
    }

    public function getStatusMessage(): string {
        return match($this->status) {
            self::LOGIN_SUCCESS => 'Login successful',
            self::LOGIN_FAILED_CLIENT => 'Login failed: Client outdated',
            self::LOGIN_FAILED_SERVER => 'Login failed: Server outdated',
            self::PLAYER_SPAWN => 'Player spawned',
            self::LOGIN_FAILED_INVALID_TENANT => 'Login failed: Invalid tenant',
            self::LOGIN_FAILED_VANILLA_EDU => 'Login failed: Vanilla to Education Edition',
            self::LOGIN_FAILED_EDU_VANILLA => 'Login failed: Education to Vanilla Edition',
            self::LOGIN_FAILED_SERVER_FULL => 'Login failed: Server full',
            default => 'Unknown status'
        };
    }

    public function handle(object $handler): bool {
        return $handler->handlePlayStatus($this);
    }
}