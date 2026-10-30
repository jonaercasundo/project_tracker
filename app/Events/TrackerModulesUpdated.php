<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class TrackerModulesUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $eventId;

    /**
     * @param  array<int, string>  $modules
     */
    public function __construct(
        public int $companyId,
        public array $modules,
    ) {
        $this->eventId = (string) Str::uuid();
    }

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('tracker.company.'.$this->companyId)];
    }

    public function broadcastAs(): string
    {
        return 'tracker.modules.updated';
    }

    /** @return array{eventId: string, modules: array<int, string>, occurredAt: string} */
    public function broadcastWith(): array
    {
        return [
            'eventId' => $this->eventId,
            'modules' => $this->modules,
            'occurredAt' => now()->toIso8601String(),
        ];
    }
}
