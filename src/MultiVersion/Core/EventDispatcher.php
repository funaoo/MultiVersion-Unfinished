<?php

declare(strict_types=1);

namespace MultiVersion\Core;

use MultiVersion\MultiVersion;
use MultiVersion\Events\Event;

final class EventDispatcher {

    private MultiVersion $plugin;
    private array $listeners = [];

    public function __construct(MultiVersion $plugin){
        $this->plugin = $plugin;
    }

    public function dispatch(Event $event): void{
        $eventClass = get_class($event);

        if(isset($this->listeners[$eventClass])){
            foreach($this->listeners[$eventClass] as $listener){
                if($event->isCancelled()){
                    break;
                }
                $listener($event);
            }
        }
    }

    public function registerListener(string $eventClass, callable $listener): void{
        $this->listeners[$eventClass][] = $listener;
    }
}
