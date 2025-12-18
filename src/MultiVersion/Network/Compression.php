<?php
declare(strict_types=1);

namespace MultiVersion\Network;

final class Compression {

    public const ALGORITHM_ZLIB = 0;
    public const ALGORITHM_SNAPPY = 1;
    public const ALGORITHM_NONE = 0xff;

    private int $threshold;
    private int $algorithm;
    private int $compressionLevel;

    public function __construct(int $threshold = 256, int $algorithm = self::ALGORITHM_ZLIB, int $compressionLevel = 7) {
        $this->threshold = $threshold;
        $this->algorithm = $algorithm;
        $this->compressionLevel = $compressionLevel;
    }

    public function compress(string $data): string {
        if (strlen($data) < $this->threshold) {
            return $data;
        }

        return match($this->algorithm) {
            self::ALGORITHM_ZLIB => $this->compressZlib($data),
            self::ALGORITHM_SNAPPY => $this->compressSnappy($data),
            default => $data
        };
    }

    public function decompress(string $data): string {
        return match($this->algorithm) {
            self::ALGORITHM_ZLIB => $this->decompressZlib($data),
            self::ALGORITHM_SNAPPY => $this->decompressSnappy($data),
            default => $data
        };
    }

    private function compressZlib(string $data): string {
        $compressed = zlib_encode($data, ZLIB_ENCODING_RAW, $this->compressionLevel);

        if ($compressed === false) {
            throw new \RuntimeException("Failed to compress data with zlib");
        }

        return $compressed;
    }

    private function decompressZlib(string $data): string {
        $decompressed = zlib_decode($data);

        if ($decompressed === false) {
            throw new \RuntimeException("Failed to decompress data with zlib");
        }

        return $decompressed;
    }

    private function compressSnappy(string $data): string {
        if (!function_exists('snappy_compress')) {
            throw new \RuntimeException("Snappy extension not available");
        }

        $compressed = snappy_compress($data);

        if ($compressed === false) {
            throw new \RuntimeException("Failed to compress data with snappy");
        }

        return $compressed;
    }

    private function decompressSnappy(string $data): string {
        if (!function_exists('snappy_uncompress')) {
            throw new \RuntimeException("Snappy extension not available");
        }

        $decompressed = snappy_uncompress($data);

        if ($decompressed === false) {
            throw new \RuntimeException("Failed to decompress data with snappy");
        }

        return $decompressed;
    }

    public function getThreshold(): int {
        return $this->threshold;
    }

    public function setThreshold(int $threshold): void {
        $this->threshold = max(0, $threshold);
    }

    public function getAlgorithm(): int {
        return $this->algorithm;
    }

    public function setAlgorithm(int $algorithm): void {
        $validAlgorithms = [self::ALGORITHM_ZLIB, self::ALGORITHM_SNAPPY, self::ALGORITHM_NONE];

        if (!in_array($algorithm, $validAlgorithms, true)) {
            throw new \InvalidArgumentException("Invalid compression algorithm: {$algorithm}");
        }

        $this->algorithm = $algorithm;
    }

    public function getCompressionLevel(): int {
        return $this->compressionLevel;
    }

    public function setCompressionLevel(int $level): void {
        $this->compressionLevel = max(0, min(9, $level));
    }

    public function isCompressionEnabled(): bool {
        return $this->algorithm !== self::ALGORITHM_NONE;
    }

    public function getCompressionRatio(string $original, string $compressed): float {
        $originalSize = strlen($original);
        $compressedSize = strlen($compressed);

        if ($originalSize === 0) {
            return 0.0;
        }

        return ($originalSize - $compressedSize) / $originalSize * 100;
    }
}