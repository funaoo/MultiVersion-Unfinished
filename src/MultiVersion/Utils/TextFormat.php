<?php

declare(strict_types=1);

namespace MultiVersion\Utils;

final class TextFormat {

    public const ESCAPE = "\xc2\xa7";

    public const BLACK = self::ESCAPE . "0";
    public const DARK_BLUE = self::ESCAPE . "1";
    public const DARK_GREEN = self::ESCAPE . "2";
    public const DARK_AQUA = self::ESCAPE . "3";
    public const DARK_RED = self::ESCAPE . "4";
    public const DARK_PURPLE = self::ESCAPE . "5";
    public const GOLD = self::ESCAPE . "6";
    public const GRAY = self::ESCAPE . "7";
    public const DARK_GRAY = self::ESCAPE . "8";
    public const BLUE = self::ESCAPE . "9";
    public const GREEN = self::ESCAPE . "a";
    public const AQUA = self::ESCAPE . "b";
    public const RED = self::ESCAPE . "c";
    public const LIGHT_PURPLE = self::ESCAPE . "d";
    public const YELLOW = self::ESCAPE . "e";
    public const WHITE = self::ESCAPE . "f";
    public const MINECOIN_GOLD = self::ESCAPE . "g";

    public const OBFUSCATED = self::ESCAPE . "k";
    public const BOLD = self::ESCAPE . "l";
    public const STRIKETHROUGH = self::ESCAPE . "m";
    public const UNDERLINE = self::ESCAPE . "n";
    public const ITALIC = self::ESCAPE . "o";
    public const RESET = self::ESCAPE . "r";

    private static array $colorMap = [
        'black' => self::BLACK,
        'dark_blue' => self::DARK_BLUE,
        'dark_green' => self::DARK_GREEN,
        'dark_aqua' => self::DARK_AQUA,
        'dark_red' => self::DARK_RED,
        'dark_purple' => self::DARK_PURPLE,
        'gold' => self::GOLD,
        'gray' => self::GRAY,
        'dark_gray' => self::DARK_GRAY,
        'blue' => self::BLUE,
        'green' => self::GREEN,
        'aqua' => self::AQUA,
        'red' => self::RED,
        'light_purple' => self::LIGHT_PURPLE,
        'yellow' => self::YELLOW,
        'white' => self::WHITE,
        'minecoin_gold' => self::MINECOIN_GOLD
    ];

    private static array $formatMap = [
        'obfuscated' => self::OBFUSCATED,
        'bold' => self::BOLD,
        'strikethrough' => self::STRIKETHROUGH,
        'underline' => self::UNDERLINE,
        'italic' => self::ITALIC,
        'reset' => self::RESET
    ];

    public static function clean(string $text, bool $removeFormat = true): string {
        if ($removeFormat) {
            return preg_replace('/' . self::ESCAPE . '[0-9a-gk-or]/u', '', $text);
        }

        return preg_replace('/' . self::ESCAPE . '[0-9a-g]/u', '', $text);
    }

    public static function toANSI(string $text): string {
        $ansiCodes = [
            self::BLACK => "\033[0;30m",
            self::DARK_BLUE => "\033[0;34m",
            self::DARK_GREEN => "\033[0;32m",
            self::DARK_AQUA => "\033[0;36m",
            self::DARK_RED => "\033[0;31m",
            self::DARK_PURPLE => "\033[0;35m",
            self::GOLD => "\033[0;33m",
            self::GRAY => "\033[0;37m",
            self::DARK_GRAY => "\033[1;30m",
            self::BLUE => "\033[1;34m",
            self::GREEN => "\033[1;32m",
            self::AQUA => "\033[1;36m",
            self::RED => "\033[1;31m",
            self::LIGHT_PURPLE => "\033[1;35m",
            self::YELLOW => "\033[1;33m",
            self::WHITE => "\033[1;37m",
            self::BOLD => "\033[1m",
            self::OBFUSCATED => "\033[5m",
            self::ITALIC => "\033[3m",
            self::UNDERLINE => "\033[4m",
            self::STRIKETHROUGH => "\033[9m",
            self::RESET => "\033[0m"
        ];

        return str_replace(array_keys($ansiCodes), array_values($ansiCodes), $text);
    }

    public static function toHTML(string $text): string {
        $htmlColors = [
            self::BLACK => '<span style="color:#000000">',
            self::DARK_BLUE => '<span style="color:#0000AA">',
            self::DARK_GREEN => '<span style="color:#00AA00">',
            self::DARK_AQUA => '<span style="color:#00AAAA">',
            self::DARK_RED => '<span style="color:#AA0000">',
            self::DARK_PURPLE => '<span style="color:#AA00AA">',
            self::GOLD => '<span style="color:#FFAA00">',
            self::GRAY => '<span style="color:#AAAAAA">',
            self::DARK_GRAY => '<span style="color:#555555">',
            self::BLUE => '<span style="color:#5555FF">',
            self::GREEN => '<span style="color:#55FF55">',
            self::AQUA => '<span style="color:#55FFFF">',
            self::RED => '<span style="color:#FF5555">',
            self::LIGHT_PURPLE => '<span style="color:#FF55FF">',
            self::YELLOW => '<span style="color:#FFFF55">',
            self::WHITE => '<span style="color:#FFFFFF">',
            self::MINECOIN_GOLD => '<span style="color:#DDD605">',
            self::BOLD => '<b>',
            self::ITALIC => '<i>',
            self::UNDERLINE => '<u>',
            self::STRIKETHROUGH => '<s>',
            self::RESET => '</span>'
        ];

        $text = htmlspecialchars($text, ENT_QUOTES);
        return str_replace(array_keys($htmlColors), array_values($htmlColors), $text);
    }

    public static function colorize(string $text): string {
        return preg_replace_callback('/&([0-9a-fk-or])/i', function($matches) {
            return self::ESCAPE . strtolower($matches[1]);
        }, $text);
    }

    public static function getColor(string $colorName): ?string {
        return self::$colorMap[strtolower($colorName)] ?? null;
    }

    public static function getFormat(string $formatName): ?string {
        return self::$formatMap[strtolower($formatName)] ?? null;
    }

    public static function wrap(string $text, string $color): string {
        return $color . $text . self::RESET;
    }

    public static function center(string $text, int $width = 50): string {
        $cleanText = self::clean($text);
        $padding = max(0, ($width - strlen($cleanText)) / 2);
        return str_repeat(' ', (int)$padding) . $text;
    }

    public static function rainbow(string $text): string {
        $colors = [
            self::RED,
            self::GOLD,
            self::YELLOW,
            self::GREEN,
            self::AQUA,
            self::BLUE,
            self::LIGHT_PURPLE
        ];

        $result = '';
        $colorIndex = 0;
        $cleanText = self::clean($text);

        for ($i = 0; $i < strlen($cleanText); $i++) {
            if ($cleanText[$i] !== ' ') {
                $result .= $colors[$colorIndex % count($colors)];
                $colorIndex++;
            }
            $result .= $cleanText[$i];
        }

        return $result . self::RESET;
    }

    public static function gradient(string $text, string $startColor, string $endColor): string {
        $cleanText = self::clean($text);
        $length = strlen($cleanText);

        if ($length <= 1) {
            return $startColor . $text . self::RESET;
        }

        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $ratio = $i / ($length - 1);
            $color = self::interpolateColor($startColor, $endColor, $ratio);
            $result .= $color . $cleanText[$i];
        }

        return $result . self::RESET;
    }

    private static function interpolateColor(string $color1, string $color2, float $ratio): string {
        return $color1;
    }

    public static function length(string $text): int {
        return strlen(self::clean($text));
    }

    public static function truncate(string $text, int $maxLength, string $suffix = '...'): string {
        $cleanText = self::clean($text);

        if (strlen($cleanText) <= $maxLength) {
            return $text;
        }

        return substr($text, 0, $maxLength - strlen($suffix)) . $suffix;
    }

    public static function hasFormatting(string $text): bool {
        return preg_match('/' . self::ESCAPE . '[0-9a-gk-or]/u', $text) === 1;
    }

    public static function getAllColors(): array {
        return self::$colorMap;
    }

    public static function getAllFormats(): array {
        return self::$formatMap;
    }
}