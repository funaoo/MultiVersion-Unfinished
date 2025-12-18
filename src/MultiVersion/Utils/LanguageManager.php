<?php
declare(strict_types=1);

namespace MultiVersion\Utils;

use MultiVersion\MultiVersion;
use Symfony\Component\Yaml\Yaml;

final class LanguageManager {

    private MultiVersion $plugin;
    private array $languages = [];
    private string $defaultLanguage = 'en_US';
    private array $playerLanguages = [];
    private array $availableLanguages = [];

    public function __construct(MultiVersion $plugin) {
        $this->plugin = $plugin;
        $this->loadLanguages();
    }

    private function loadLanguages(): void {
        $languagesPath = $this->plugin->getDataFolder() . 'languages/';

        if (!is_dir($languagesPath)) {
            mkdir($languagesPath, 0755, true);
            $this->saveDefaultLanguages();
        }

        $files = glob($languagesPath . '*.yml');

        foreach ($files as $file) {
            $langCode = basename($file, '.yml');
            $this->loadLanguage($langCode, $file);
        }

        if (empty($this->languages)) {
            $this->plugin->getMVLogger()->warning("No language files found, using defaults");
            $this->loadDefaultLanguage();
        }
    }

    private function loadLanguage(string $langCode, string $file): void {
        try {
            $content = file_get_contents($file);
            $data = Yaml::parse($content);

            if (isset($data['multiversion'])) {
                $this->languages[$langCode] = $data;
                $this->availableLanguages[] = $langCode;
                $this->plugin->getMVLogger()->info("Loaded language: {$langCode}");
            }
        } catch (\Exception $e) {
            $this->plugin->getMVLogger()->error("Failed to load language {$langCode}: {$e->getMessage()}");
        }
    }

    private function saveDefaultLanguages(): void {
        $languagesPath = $this->plugin->getDataFolder() . 'languages/';

        $this->plugin->saveResource('languages/en_US.yml', false);
        $this->plugin->saveResource('languages/es_ES.yml', false);
    }

    private function loadDefaultLanguage(): void {
        $this->languages[$this->defaultLanguage] = [
            'multiversion' => [
                'prefix' => '§8[§bMultiVersion§8]§r',
                'messages' => [
                    'error' => [
                        'generic' => '§cAn error occurred'
                    ]
                ]
            ]
        ];
    }

    public function getMessage(string $key, string $language = null, array $params = []): string {
        $language = $language ?? $this->defaultLanguage;

        if (!isset($this->languages[$language])) {
            $language = $this->defaultLanguage;
        }

        $message = $this->getMessageFromPath($key, $language);

        if ($message === null) {
            if ($language !== $this->defaultLanguage) {
                $message = $this->getMessageFromPath($key, $this->defaultLanguage);
            }

            if ($message === null) {
                return $key;
            }
        }

        return $this->replacePlaceholders($message, $params);
    }

    private function getMessageFromPath(string $path, string $language): ?string {
        $keys = explode('.', $path);
        $data = $this->languages[$language] ?? [];

        $current = $data;
        foreach ($keys as $key) {
            if (!isset($current[$key])) {
                return null;
            }
            $current = $current[$key];
        }

        return is_string($current) ? $current : null;
    }

    private function replacePlaceholders(string $message, array $params): string {
        foreach ($params as $key => $value) {
            $message = str_replace('{' . $key . '}', (string)$value, $message);
        }
        return $message;
    }

    public function getPrefix(string $language = null): string {
        $language = $language ?? $this->defaultLanguage;
        return $this->getMessage('multiversion.prefix', $language);
    }

    public function setPlayerLanguage(string $playerName, string $language): void {
        if (in_array($language, $this->availableLanguages, true)) {
            $this->playerLanguages[$playerName] = $language;
        }
    }

    public function getPlayerLanguage(string $playerName): string {
        return $this->playerLanguages[$playerName] ?? $this->defaultLanguage;
    }

    public function removePlayerLanguage(string $playerName): void {
        unset($this->playerLanguages[$playerName]);
    }

    public function getAvailableLanguages(): array {
        return $this->availableLanguages;
    }

    public function getDefaultLanguage(): string {
        return $this->defaultLanguage;
    }

    public function setDefaultLanguage(string $language): void {
        if (isset($this->languages[$language])) {
            $this->defaultLanguage = $language;
        }
    }

    public function isLanguageAvailable(string $language): bool {
        return in_array($language, $this->availableLanguages, true);
    }

    public function translateForPlayer(string $playerName, string $key, array $params = []): string {
        $language = $this->getPlayerLanguage($playerName);
        return $this->getMessage($key, $language, $params);
    }

    public function reload(): void {
        $this->languages = [];
        $this->availableLanguages = [];
        $this->loadLanguages();
    }
}