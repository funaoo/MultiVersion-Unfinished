<?php

declare(strict_types=1);

namespace MultiVersion\Utils;

use pocketmine\utils\TextFormat;

final class Logger {

    private string $logPath;
    private string $logLevel;
    private array $levelPriority = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3,
        'CRITICAL' => 4
    ];
    private bool $colorEnabled = true;
    private array $logBuffer = [];
    private int $bufferSize = 100;

    public function __construct(string $logPath, string $logLevel = 'INFO') {
        $this->logPath = $logPath;
        $this->logLevel = strtoupper($logLevel);
        $this->ensureLogDirectory();
    }

    private function ensureLogDirectory(): void {
        $dir = dirname($this->logPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    public function debug(string $message, array $context = []): void {
        $this->log('DEBUG', $message, $context);
    }

    public function info(string $message, array $context = []): void {
        $this->log('INFO', $message, $context);
    }

    public function warning(string $message, array $context = []): void {
        $this->log('WARNING', $message, $context);
    }

    public function error(string $message, array $context = []): void {
        $this->log('ERROR', $message, $context);
    }

    public function critical(string $message, array $context = []): void {
        $this->log('CRITICAL', $message, $context);
    }

    private function log(string $level, string $message, array $context = []): void {
        if (!$this->shouldLog($level)) {
            return;
        }

        $formattedMessage = $this->formatMessage($level, $message, $context);
        $this->writeToFile($formattedMessage);
        $this->writeToConsole($level, $formattedMessage);
        $this->bufferLog($level, $message, $context);
    }

    private function shouldLog(string $level): bool {
        $currentPriority = $this->levelPriority[$this->logLevel] ?? 1;
        $messagePriority = $this->levelPriority[$level] ?? 1;

        return $messagePriority >= $currentPriority;
    }

    private function formatMessage(string $level, string $message, array $context): string {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';

        return "[{$timestamp}] [{$level}] {$message}{$contextStr}";
    }

    private function writeToFile(string $message): void {
        $logFile = $this->getLogFile();

        file_put_contents($logFile, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function getLogFile(): string {
        $date = date('Y-m-d');
        $dir = dirname($this->logPath);
        $filename = basename($this->logPath, '.log');

        return "{$dir}/{$filename}-{$date}.log";
    }

    private function writeToConsole(string $level, string $message): void {
        if (!$this->colorEnabled) {
            echo $message . PHP_EOL;
            return;
        }

        $coloredMessage = $this->colorizeMessage($level, $message);
        echo $coloredMessage . PHP_EOL;
    }

    private function colorizeMessage(string $level, string $message): string {
        $color = match($level) {
            'DEBUG' => TextFormat::GRAY,
            'INFO' => TextFormat::GREEN,
            'WARNING' => TextFormat::YELLOW,
            'ERROR' => TextFormat::RED,
            'CRITICAL' => TextFormat::DARK_RED,
            default => TextFormat::WHITE
        };

        return $color . $message . TextFormat::RESET;
    }

    private function bufferLog(string $level, string $message, array $context): void {
        $this->logBuffer[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'timestamp' => microtime(true)
        ];

        if (count($this->logBuffer) > $this->bufferSize) {
            array_shift($this->logBuffer);
        }
    }

    public function setLogLevel(string $level): void {
        $level = strtoupper($level);

        if (isset($this->levelPriority[$level])) {
            $this->logLevel = $level;
        }
    }

    public function getLogLevel(): string {
        return $this->logLevel;
    }

    public function enableColor(bool $enabled): void {
        $this->colorEnabled = $enabled;
    }

    public function getBuffer(): array {
        return $this->logBuffer;
    }

    public function clearBuffer(): void {
        $this->logBuffer = [];
    }

    public function getLogFiles(): array {
        $dir = dirname($this->logPath);
        $filename = basename($this->logPath, '.log');
        $pattern = "{$dir}/{$filename}-*.log";

        return glob($pattern) ?: [];
    }

    public function cleanOldLogs(int $daysToKeep = 7): int {
        $files = $this->getLogFiles();
        $deletedCount = 0;
        $cutoffTime = time() - ($daysToKeep * 86400);

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                if (unlink($file)) {
                    $deletedCount++;
                }
            }
        }

        return $deletedCount;
    }

    public function getLogSize(): int {
        $files = $this->getLogFiles();
        $totalSize = 0;

        foreach ($files as $file) {
            $totalSize += filesize($file);
        }

        return $totalSize;
    }

    public function exportLogs(string $outputPath): bool {
        $files = $this->getLogFiles();

        if (empty($files)) {
            return false;
        }

        $allLogs = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $allLogs[] = "=== " . basename($file) . " ===" . PHP_EOL;
            $allLogs[] = $content;
            $allLogs[] = PHP_EOL;
        }

        return file_put_contents($outputPath, implode('', $allLogs)) !== false;
    }
}