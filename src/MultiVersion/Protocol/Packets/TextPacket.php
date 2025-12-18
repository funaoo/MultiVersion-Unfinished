<?php

declare(strict_types=1);

namespace MultiVersion\Protocol\Packets;

use MultiVersion\Protocol\Packets\BasePacket;
use MultiVersion\Utils\BinaryStream;

final class TextPacket extends BasePacket {

    public const TYPE_RAW = 0;
    public const TYPE_CHAT = 1;
    public const TYPE_TRANSLATION = 2;
    public const TYPE_POPUP = 3;
    public const TYPE_JUKEBOX_POPUP = 4;
    public const TYPE_TIP = 5;
    public const TYPE_SYSTEM = 6;
    public const TYPE_WHISPER = 7;
    public const TYPE_ANNOUNCEMENT = 8;
    public const TYPE_JSON = 9;
    public const TYPE_JSON_WHISPER = 10;

    public int $type;
    public bool $needsTranslation;
    public string $sourceName;
    public string $message;
    public array $parameters = [];
    public string $xboxUserId;
    public string $platformChatId;

    public function __construct() {
        $this->packetId = 0x09;
    }

    public function encode(): void {
        $stream = $this->getBuffer();
        $this->encodeHeader($stream);

        $stream->putByte($this->type);
        $stream->putBool($this->needsTranslation);

        match($this->type) {
            self::TYPE_CHAT, self::TYPE_WHISPER, self::TYPE_ANNOUNCEMENT => $this->encodeChatMessage($stream),
            self::TYPE_RAW, self::TYPE_TIP, self::TYPE_SYSTEM, self::TYPE_JSON, self::TYPE_JSON_WHISPER => $this->encodeRawMessage($stream),
            self::TYPE_TRANSLATION, self::TYPE_POPUP, self::TYPE_JUKEBOX_POPUP => $this->encodeTranslationMessage($stream),
            default => $this->encodeRawMessage($stream)
        };

        $stream->putString($this->xboxUserId);
        $stream->putString($this->platformChatId);

        $this->encoded = true;
    }

    public function decode(): void {
        $stream = $this->getBuffer();
        $this->decodeHeader($stream);

        $this->type = $stream->getByte();
        $this->needsTranslation = $stream->getBool();

        match($this->type) {
            self::TYPE_CHAT, self::TYPE_WHISPER, self::TYPE_ANNOUNCEMENT => $this->decodeChatMessage($stream),
            self::TYPE_RAW, self::TYPE_TIP, self::TYPE_SYSTEM, self::TYPE_JSON, self::TYPE_JSON_WHISPER => $this->decodeRawMessage($stream),
            self::TYPE_TRANSLATION, self::TYPE_POPUP, self::TYPE_JUKEBOX_POPUP => $this->decodeTranslationMessage($stream),
            default => $this->decodeRawMessage($stream)
        };

        $this->xboxUserId = $stream->getString();
        $this->platformChatId = $stream->getString();

        $this->decoded = true;
    }

    private function encodeChatMessage(BinaryStream $stream): void {
        $stream->putString($this->sourceName);
        $stream->putString($this->message);
    }

    private function decodeChatMessage(BinaryStream $stream): void {
        $this->sourceName = $stream->getString();
        $this->message = $stream->getString();
    }

    private function encodeRawMessage(BinaryStream $stream): void {
        $stream->putString($this->message);
    }

    private function decodeRawMessage(BinaryStream $stream): void {
        $this->message = $stream->getString();
    }

    private function encodeTranslationMessage(BinaryStream $stream): void {
        $stream->putString($this->message);
        $stream->putUnsignedVarInt(count($this->parameters));
        foreach ($this->parameters as $parameter) {
            $stream->putString($parameter);
        }
    }

    private function decodeTranslationMessage(BinaryStream $stream): void {
        $this->message = $stream->getString();
        $count = $stream->getUnsignedVarInt();
        $this->parameters = [];
        for ($i = 0; $i < $count; $i++) {
            $this->parameters[] = $stream->getString();
        }
    }

    public function isChat(): bool {
        return $this->type === self::TYPE_CHAT;
    }

    public function isWhisper(): bool {
        return $this->type === self::TYPE_WHISPER;
    }

    public function isAnnouncement(): bool {
        return $this->type === self::TYPE_ANNOUNCEMENT;
    }

    public function isSystem(): bool {
        return $this->type === self::TYPE_SYSTEM;
    }

    public function isTranslation(): bool {
        return $this->type === self::TYPE_TRANSLATION;
    }

    public function isPopup(): bool {
        return $this->type === self::TYPE_POPUP;
    }

    public function isTip(): bool {
        return $this->type === self::TYPE_TIP;
    }

    public static function chat(string $sourceName, string $message): self {
        $packet = new self();
        $packet->type = self::TYPE_CHAT;
        $packet->needsTranslation = false;
        $packet->sourceName = $sourceName;
        $packet->message = $message;
        $packet->xboxUserId = '';
        $packet->platformChatId = '';
        return $packet;
    }

    public static function raw(string $message): self {
        $packet = new self();
        $packet->type = self::TYPE_RAW;
        $packet->needsTranslation = false;
        $packet->message = $message;
        $packet->xboxUserId = '';
        $packet->platformChatId = '';
        return $packet;
    }

    public static function translation(string $message, array $parameters = []): self {
        $packet = new self();
        $packet->type = self::TYPE_TRANSLATION;
        $packet->needsTranslation = true;
        $packet->message = $message;
        $packet->parameters = $parameters;
        $packet->xboxUserId = '';
        $packet->platformChatId = '';
        return $packet;
    }

    public static function popup(string $message): self {
        $packet = new self();
        $packet->type = self::TYPE_POPUP;
        $packet->needsTranslation = false;
        $packet->message = $message;
        $packet->xboxUserId = '';
        $packet->platformChatId = '';
        return $packet;
    }

    public static function tip(string $message): self {
        $packet = new self();
        $packet->type = self::TYPE_TIP;
        $packet->needsTranslation = false;
        $packet->message = $message;
        $packet->xboxUserId = '';
        $packet->platformChatId = '';
        return $packet;
    }

    public static function system(string $message): self {
        $packet = new self();
        $packet->type = self::TYPE_SYSTEM;
        $packet->needsTranslation = false;
        $packet->message = $message;
        $packet->xboxUserId = '';
        $packet->platformChatId = '';
        return $packet;
    }

    public function handle(object $handler): bool {
        return $handler->handleText($this);
    }
}