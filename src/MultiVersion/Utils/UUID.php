<?php

declare(strict_types=1);

namespace MultiVersion\Utils;

final class UUID {

    private string $uuid;
    private array $parts;

    public function __construct(string $uuid) {
        $this->uuid = $this->normalize($uuid);
        $this->parts = $this->parse($this->uuid);
    }

    private function normalize(string $uuid): string {
        $uuid = str_replace(['{', '}', '-'], '', strtolower($uuid));

        if (strlen($uuid) !== 32) {
            throw new \InvalidArgumentException("Invalid UUID format");
        }

        if (!ctype_xdigit($uuid)) {
            throw new \InvalidArgumentException("UUID must contain only hexadecimal characters");
        }

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($uuid, 0, 8),
            substr($uuid, 8, 4),
            substr($uuid, 12, 4),
            substr($uuid, 16, 4),
            substr($uuid, 20, 12)
        );
    }

    private function parse(string $uuid): array {
        $parts = explode('-', $uuid);

        return [
            'time_low' => hexdec($parts[0]),
            'time_mid' => hexdec($parts[1]),
            'time_hi_version' => hexdec($parts[2]),
            'clock_seq' => hexdec($parts[3]),
            'node' => $parts[4]
        ];
    }

    public static function generate(): self {
        $data = random_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        $uuid = bin2hex($data);

        return new self($uuid);
    }

    public static function generateV4(): self {
        return self::generate();
    }

    public static function generateV3(string $namespace, string $name): self {
        $hash = md5($namespace . $name);

        $data = hex2bin($hash);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x30);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        $uuid = bin2hex($data);

        return new self($uuid);
    }

    public static function generateV5(string $namespace, string $name): self {
        $hash = sha1($namespace . $name);

        $data = hex2bin(substr($hash, 0, 32));
        $data[6] = chr(ord($data[6]) & 0x0f | 0x50);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        $uuid = bin2hex($data);

        return new self($uuid);
    }

    public static function fromString(string $uuid): self {
        return new self($uuid);
    }

    public static function fromBytes(string $bytes): self {
        if (strlen($bytes) !== 16) {
            throw new \InvalidArgumentException("Bytes must be exactly 16 bytes long");
        }

        $uuid = bin2hex($bytes);
        return new self($uuid);
    }

    public function toString(): string {
        return $this->uuid;
    }

    public function toBytes(): string {
        $hex = str_replace('-', '', $this->uuid);
        return hex2bin($hex);
    }

    public function toShortString(): string {
        return str_replace('-', '', $this->uuid);
    }

    public function getVersion(): int {
        return (int)hexdec(substr($this->uuid, 14, 1));
    }

    public function getVariant(): int {
        $clockSeq = $this->parts['clock_seq'];

        if (($clockSeq & 0x8000) === 0) {
            return 0;
        }

        if (($clockSeq & 0xC000) === 0x8000) {
            return 2;
        }

        if (($clockSeq & 0xE000) === 0xC000) {
            return 6;
        }

        return 7;
    }

    public function getTimeLow(): int {
        return $this->parts['time_low'];
    }

    public function getTimeMid(): int {
        return $this->parts['time_mid'];
    }

    public function getTimeHiVersion(): int {
        return $this->parts['time_hi_version'];
    }

    public function getClockSeq(): int {
        return $this->parts['clock_seq'];
    }

    public function getNode(): string {
        return $this->parts['node'];
    }

    public function equals(UUID $other): bool {
        return $this->uuid === $other->uuid;
    }

    public function compareTo(UUID $other): int {
        return strcmp($this->uuid, $other->uuid);
    }

    public function isNil(): bool {
        return $this->uuid === '00000000-0000-0000-0000-000000000000';
    }

    public static function nil(): self {
        return new self('00000000-0000-0000-0000-000000000000');
    }

    public static function isValid(string $uuid): bool {
        try {
            new self($uuid);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function __toString(): string {
        return $this->uuid;
    }

    public function jsonSerialize(): string {
        return $this->uuid;
    }

    public static function namespaceDNS(): string {
        return '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
    }

    public static function namespaceURL(): string {
        return '6ba7b811-9dad-11d1-80b4-00c04fd430c8';
    }

    public static function namespaceOID(): string {
        return '6ba7b812-9dad-11d1-80b4-00c04fd430c8';
    }

    public static function namespaceX500(): string {
        return '6ba7b814-9dad-11d1-80b4-00c04fd430c8';
    }
}