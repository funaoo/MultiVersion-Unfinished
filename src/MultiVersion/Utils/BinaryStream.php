<?php

declare(strict_types=1);

namespace MultiVersion\Utils;

final class BinaryStream {

    private string $buffer;
    private int $offset;

    public function __construct(string $buffer = '', int $offset = 0) {
        $this->buffer = $buffer;
        $this->offset = $offset;
    }

    public function reset(): void {
        $this->buffer = '';
        $this->offset = 0;
    }

    public function setBuffer(string $buffer, int $offset = 0): void {
        $this->buffer = $buffer;
        $this->offset = $offset;
    }

    public function getOffset(): int {
        return $this->offset;
    }

    public function getBuffer(): string {
        return $this->buffer;
    }

    public function get(int $len): string {
        if ($len < 0) {
            throw new \InvalidArgumentException("Length must be positive");
        }

        if ($len === 0) {
            return "";
        }

        $remaining = strlen($this->buffer) - $this->offset;

        if ($remaining < $len) {
            throw new \UnderflowException("Not enough bytes in buffer");
        }

        $data = substr($this->buffer, $this->offset, $len);
        $this->offset += $len;

        return $data;
    }

    public function put(string $str): void {
        $this->buffer .= $str;
    }

    public function getBool(): bool {
        return $this->getByte() !== 0;
    }

    public function putBool(bool $v): void {
        $this->putByte($v ? 1 : 0);
    }

    public function getByte(): int {
        return ord($this->get(1));
    }

    public function putByte(int $v): void {
        $this->buffer .= chr($v);
    }

    public function getShort(): int {
        return unpack('n', $this->get(2))[1] << 48 >> 48;
    }

    public function putShort(int $v): void {
        $this->buffer .= pack('n', $v);
    }

    public function getLShort(): int {
        return unpack('v', $this->get(2))[1] << 48 >> 48;
    }

    public function putLShort(int $v): void {
        $this->buffer .= pack('v', $v);
    }

    public function getTriad(): int {
        return unpack('N', "\x00" . $this->get(3))[1];
    }

    public function putTriad(int $v): void {
        $this->buffer .= substr(pack('N', $v), 1);
    }

    public function getLTriad(): int {
        return unpack('V', $this->get(3) . "\x00")[1];
    }

    public function putLTriad(int $v): void {
        $this->buffer .= substr(pack('V', $v), 0, 3);
    }

    public function getInt(): int {
        return unpack('N', $this->get(4))[1] << 32 >> 32;
    }

    public function putInt(int $v): void {
        $this->buffer .= pack('N', $v);
    }

    public function getLInt(): int {
        return unpack('V', $this->get(4))[1] << 32 >> 32;
    }

    public function putLInt(int $v): void {
        $this->buffer .= pack('V', $v);
    }

    public function getFloat(): float {
        return unpack('G', $this->get(4))[1];
    }

    public function putFloat(float $v): void {
        $this->buffer .= pack('G', $v);
    }

    public function getLFloat(): float {
        return unpack('g', $this->get(4))[1];
    }

    public function putLFloat(float $v): void {
        $this->buffer .= pack('g', $v);
    }

    public function getDouble(): float {
        return unpack('E', $this->get(8))[1];
    }

    public function putDouble(float $v): void {
        $this->buffer .= pack('E', $v);
    }

    public function getLDouble(): float {
        return unpack('e', $this->get(8))[1];
    }

    public function putLDouble(float $v): void {
        $this->buffer .= pack('e', $v);
    }

    public function getLong(): int {
        return unpack('J', $this->get(8))[1];
    }

    public function putLong(int $v): void {
        $this->buffer .= pack('J', $v);
    }

    public function getLLong(): int {
        return unpack('P', $this->get(8))[1];
    }

    public function putLLong(int $v): void {
        $this->buffer .= pack('P', $v);
    }

    public function getUnsignedVarInt(): int {
        $value = 0;
        $shift = 0;

        do {
            $b = $this->getByte();
            $value |= ($b & 0x7f) << $shift;
            $shift += 7;

            if ($shift >= 64) {
                throw new \OverflowException("VarInt is too large");
            }
        } while ($b & 0x80);

        return $value;
    }

    public function putUnsignedVarInt(int $v): void {
        $v &= 0xffffffff;

        for ($i = 0; $i < 5; ++$i) {
            $byte = $v & 0x7f;
            $v >>= 7;

            if ($v !== 0) {
                $this->putByte($byte | 0x80);
            } else {
                $this->putByte($byte);
                break;
            }
        }
    }

    public function getVarInt(): int {
        $raw = $this->getUnsignedVarInt();
        $temp = (((($raw << 63) >> 63) ^ $raw) >> 1);
        return $temp ^ ($raw & (1 << 63));
    }

    public function putVarInt(int $v): void {
        $this->putUnsignedVarInt(($v << 1) ^ ($v >> 63));
    }

    public function getUnsignedVarLong(): int {
        $value = 0;
        $shift = 0;

        do {
            $b = $this->getByte();
            $value |= ($b & 0x7f) << $shift;
            $shift += 7;

            if ($shift >= 64) {
                throw new \OverflowException("VarLong is too large");
            }
        } while ($b & 0x80);

        return $value;
    }

    public function putUnsignedVarLong(int $v): void {
        for ($i = 0; $i < 10; ++$i) {
            $byte = $v & 0x7f;
            $v >>= 7;

            if ($v !== 0) {
                $this->putByte($byte | 0x80);
            } else {
                $this->putByte($byte);
                break;
            }
        }
    }

    public function getVarLong(): int {
        $raw = $this->getUnsignedVarLong();
        $temp = (((($raw << 63) >> 63) ^ $raw) >> 1);
        return $temp ^ ($raw & (1 << 63));
    }

    public function putVarLong(int $v): void {
        $this->putUnsignedVarLong(($v << 1) ^ ($v >> 63));
    }

    public function getString(): string {
        $len = $this->getUnsignedVarInt();
        return $this->get($len);
    }

    public function putString(string $v): void {
        $this->putUnsignedVarInt(strlen($v));
        $this->put($v);
    }

    public function getUUID(): string {
        $part1 = $this->getLInt();
        $part2 = $this->getLInt();
        $part3 = $this->getLInt();
        $part4 = $this->getLInt();

        return sprintf(
            '%08x-%04x-%04x-%04x-%012x',
            $part1,
            $part2 >> 16,
            $part2 & 0xFFFF,
            $part3 >> 16,
            (($part3 & 0xFFFF) << 32) | $part4
        );
    }

    public function putUUID(string $uuid): void {
        $uuid = str_replace('-', '', $uuid);

        if (strlen($uuid) !== 32) {
            throw new \InvalidArgumentException("Invalid UUID format");
        }

        $parts = str_split($uuid, 8);

        foreach ($parts as $part) {
            $this->putLInt((int)hexdec($part));
        }
    }

    public function feof(): bool {
        return $this->offset >= strlen($this->buffer);
    }

    public function getRemaining(): int {
        return strlen($this->buffer) - $this->offset;
    }
}