<?php

declare(strict_types=1);

namespace MultiVersion\Events;

abstract class Event {

    private bool $cancelled = false;
    private float $timestamp;
    private string $eventName;

    public function __construct() {
        $this->timestamp = microtime(true);
        $this->eventName = static::class;
    }

    public function isCancelled(): bool {
        return $this->cancelled;
    }

    public function setCancelled(bool $cancelled = true): void {
        $this->cancelled = $cancelled;
    }

    public function getTimestamp(): float {
        return $this->timestamp;
    }

    public function getEventName(): string {
        return $this->eventName;
    }

    public function getAge(): float {
        return microtime(true) - $this->timestamp;
    }
}