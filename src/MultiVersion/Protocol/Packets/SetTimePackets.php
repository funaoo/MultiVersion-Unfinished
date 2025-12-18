<?php

declare(strict_types=1);

namespace MultiVersion\Protocol\Packets;

use MultiVersion\Protocol\Packets\BasePacket;

final class SetTimePacket extends BasePacket {

    public int $time;

    public function __construct() {
        $this->packetId = 0x0a;
    }

    public function encode(): void {
        $stream = $this->getBuffer();
        $this->encodeHeader($stream);

        $stream->putVarInt($this->time);

        $this->encoded = true;
    }

    public function decode(): void {
        $stream = $this->getBuffer();
        $this->decodeHeader($stream);

        $this->time = $stream->getVarInt();

        $this->decoded = true;
    }

    public function getTime(): int {
        return $this->time;
    }

    public function setTime(int $time): void {
        $this->time = $time;
    }

    public function isDaytime(): bool {
        $dayTime = $this->time % 24000;
        return $dayTime >= 0 && $dayTime < 13000;
    }

    public function isNighttime(): bool {
        return !$this->isDaytime();
    }

    public function isSunrise(): bool {
        $dayTime = $this->time % 24000;
        return $dayTime >= 23000 || $dayTime < 1000;
    }

    public function isSunset(): bool {
        $dayTime = $this->time % 24000;
        return $dayTime >= 12000 && $dayTime < 14000;
    }

    public function isNoon(): bool {
        $dayTime = $this->time % 24000;
        return $dayTime >= 5000 && $dayTime < 7000;
    }

    public function isMidnight(): bool {
        $dayTime = $this->time % 24000;
        return $dayTime >= 17000 && $dayTime < 19000;
    }

    public function getDayTime(): int {
        return $this->time % 24000;
    }

    public function getDayCount(): int {
        return (int)floor($this->time / 24000);
    }

    public function getHour(): int {
        return (int)floor(($this->time % 24000) / 1000);
    }

    public function getMinute(): int {
        $ticks = $this->time % 24000;
        $hour = floor($ticks / 1000);
        $remainder = $ticks - ($hour * 1000);
        return (int)floor($remainder / 16.67);
    }

    public function getTimeString(): string {
        $hour = $this->getHour();
        $minute = $this->getMinute();
        return sprintf('%02d:%02d', $hour, $minute);
    }

    public static function create(int $time): self {
        $packet = new self();
        $packet->time = $time;
        return $packet;
    }

    public static function createDawn(): self {
        return self::create(0);
    }

    public static function createNoon(): self {
        return self::create(6000);
    }

    public static function createDusk(): self {
        return self::create(12000);
    }

    public static function createMidnight(): self {
        return self::create(18000);
    }

    public static function fromRealTime(int $hour, int $minute): self {
        $ticks = ($hour * 1000) + (int)($minute * 16.67);
        return self::create($ticks);
    }

    public function addTime(int $ticks): void {
        $this->time += $ticks;
    }

    public function subtractTime(int $ticks): void {
        $this->time = max(0, $this->time - $ticks);
    }

    public function setDaytime(int $dayTime): void {
        $dayCount = $this->getDayCount();
        $this->time = ($dayCount * 24000) + ($dayTime % 24000);
    }

    public function nextDay(): void {
        $this->addTime(24000);
    }

    public function previousDay(): void {
        $this->subtractTime(24000);
    }

    public function handle(object $handler): bool {
        return $handler->handleSetTime($this);
    }
}