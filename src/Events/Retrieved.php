<?php

namespace Vaskiq\LaravelFileLayer\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Retrieved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly string $path,
        public readonly string $storage,
        public readonly mixed $data = null
    ) {
        //
    }
}
