<?php

declare(strict_types=1);

namespace MultiVersion\Utils;

final class VersionComparator {

    private static array $protocolVersionMap = [
        621 => '1.21.130',
        594 => '1.20.40',
        527 => '1.18.12'
    ];

    private static array $versionProtocolMap = [
        '1.21.130' => 621,
        '1.20.40' => 594,
        '1.18.12' => 527
    ];

    public static function compare(string $version1, string $version2): int {
        $v1Parts = self::parseVersion($version1);
        $v2Parts = self::parseVersion($version2);

        for ($i = 0; $i < 3; $i++) {
            $part1 = $v1Parts[$i] ?? 0;
            $part2 = $v2Parts[$i] ?? 0;

            if ($part1 !== $part2) {
                return $part1 <=> $part2;
            }
        }

        return 0;
    }

    private static function parseVersion(string $version): array {
        $version = preg_replace('/[^0-9.]/', '', $version);
        $parts = explode('.', $version);

        return array_map('intval', array_pad($parts, 3, 0));
    }

    public static function isGreaterThan(string $version1, string $version2): bool {
        return self::compare($version1, $version2) > 0;
    }

    public static function isLessThan(string $version1, string $version2): bool {
        return self::compare($version1, $version2) < 0;
    }

    public static function isEqual(string $version1, string $version2): bool {
        return self::compare($version1, $version2) === 0;
    }

    public static function isGreaterOrEqual(string $version1, string $version2): bool {
        return self::compare($version1, $version2) >= 0;
    }

    public static function isLessOrEqual(string $version1, string $version2): bool {
        return self::compare($version1, $version2) <= 0;
    }

    public static function isBetween(string $version, string $minVersion, string $maxVersion, bool $inclusive = true): bool {
        if ($inclusive) {
            return self::isGreaterOrEqual($version, $minVersion) && self::isLessOrEqual($version, $maxVersion);
        }

        return self::isGreaterThan($version, $minVersion) && self::isLessThan($version, $maxVersion);
    }

    public static function protocolToVersion(int $protocol): ?string {
        return self::$protocolVersionMap[$protocol] ?? null;
    }

    public static function versionToProtocol(string $version): ?int {
        return self::$versionProtocolMap[$version] ?? null;
    }

    public static function getLatestProtocol(): int {
        return max(array_keys(self::$protocolVersionMap));
    }

    public static function getOldestProtocol(): int {
        return min(array_keys(self::$protocolVersionMap));
    }

    public static function getLatestVersion(): string {
        $latestProtocol = self::getLatestProtocol();
        return self::$protocolVersionMap[$latestProtocol];
    }

    public static function getOldestVersion(): string {
        $oldestProtocol = self::getOldestProtocol();
        return self::$protocolVersionMap[$oldestProtocol];
    }

    public static function getAllProtocols(): array {
        return array_keys(self::$protocolVersionMap);
    }

    public static function getAllVersions(): array {
        return array_values(self::$protocolVersionMap);
    }

    public static function getProtocolVersionMap(): array {
        return self::$protocolVersionMap;
    }

    public static function isProtocolSupported(int $protocol): bool {
        return isset(self::$protocolVersionMap[$protocol]);
    }

    public static function isVersionSupported(string $version): bool {
        return isset(self::$versionProtocolMap[$version]);
    }

    public static function getClosestProtocol(int $protocol): int {
        $protocols = self::getAllProtocols();
        $closest = $protocols[0];
        $minDiff = abs($protocol - $closest);

        foreach ($protocols as $p) {
            $diff = abs($protocol - $p);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $closest = $p;
            }
        }

        return $closest;
    }

    public static function getClosestVersion(string $version): string {
        $protocols = self::getAllProtocols();
        $targetParts = self::parseVersion($version);
        $closestProtocol = $protocols[0];
        $minScore = PHP_INT_MAX;

        foreach ($protocols as $protocol) {
            $protocolVersion = self::protocolToVersion($protocol);
            $protocolParts = self::parseVersion($protocolVersion);

            $score = 0;
            for ($i = 0; $i < 3; $i++) {
                $score += abs(($targetParts[$i] ?? 0) - ($protocolParts[$i] ?? 0)) * pow(1000, 2 - $i);
            }

            if ($score < $minScore) {
                $minScore = $score;
                $closestProtocol = $protocol;
            }
        }

        return self::protocolToVersion($closestProtocol);
    }

    public static function getVersionDifference(string $version1, string $version2): array {
        $v1Parts = self::parseVersion($version1);
        $v2Parts = self::parseVersion($version2);

        return [
            'major' => ($v2Parts[0] ?? 0) - ($v1Parts[0] ?? 0),
            'minor' => ($v2Parts[1] ?? 0) - ($v1Parts[1] ?? 0),
            'patch' => ($v2Parts[2] ?? 0) - ($v1Parts[2] ?? 0)
        ];
    }

    public static function getProtocolDifference(int $protocol1, int $protocol2): int {
        return $protocol2 - $protocol1;
    }

    public static function formatVersion(string $version): string {
        $parts = self::parseVersion($version);
        return implode('.', array_slice($parts, 0, 3));
    }

    public static function isValidVersion(string $version): bool {
        return preg_match('/^\d+\.\d+(\.\d+)?$/', $version) === 1;
    }

    public static function isValidProtocol(int $protocol): bool {
        return $protocol > 0 && $protocol < 10000;
    }

    public static function getMajorVersion(string $version): int {
        $parts = self::parseVersion($version);
        return $parts[0] ?? 0;
    }

    public static function getMinorVersion(string $version): int {
        $parts = self::parseVersion($version);
        return $parts[1] ?? 0;
    }

    public static function getPatchVersion(string $version): int {
        $parts = self::parseVersion($version);
        return $parts[2] ?? 0;
    }

    public static function registerProtocol(int $protocol, string $version): void {
        self::$protocolVersionMap[$protocol] = $version;
        self::$versionProtocolMap[$version] = $protocol;
    }

    public static function unregisterProtocol(int $protocol): void {
        if (isset(self::$protocolVersionMap[$protocol])) {
            $version = self::$protocolVersionMap[$protocol];
            unset(self::$protocolVersionMap[$protocol]);
            unset(self::$versionProtocolMap[$version]);
        }
    }

    public static function getVersionInfo(string $version): array {
        $parts = self::parseVersion($version);
        $protocol = self::versionToProtocol($version);

        return [
            'version' => $version,
            'formatted' => self::formatVersion($version),
            'major' => $parts[0] ?? 0,
            'minor' => $parts[1] ?? 0,
            'patch' => $parts[2] ?? 0,
            'protocol' => $protocol,
            'supported' => $protocol !== null
        ];
    }

    public static function getProtocolInfo(int $protocol): array {
        $version = self::protocolToVersion($protocol);

        return [
            'protocol' => $protocol,
            'version' => $version,
            'supported' => $version !== null,
            'is_latest' => $protocol === self::getLatestProtocol(),
            'is_oldest' => $protocol === self::getOldestProtocol()
        ];
    }
}