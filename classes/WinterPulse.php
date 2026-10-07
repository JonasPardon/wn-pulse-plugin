<?php

declare(strict_types=1);

namespace JonasPardon\Pulse\Classes;

use Laravel\Pulse\Pulse;

/**
 * Pulse reads the current user from Laravel's `auth` binding, which Winter
 * doesn't register; backend and frontend users each have their own manager.
 */
class WinterPulse extends Pulse
{
    public function authenticatedUserIdResolver(): callable
    {
        $users = $this->app->make(WinterUsers::class);
        $key = $users->currentKey();

        if ($key !== null) {
            return fn (): string => $key;
        }

        return fn (): int|string|null => $users->currentKey() ?? $this->rememberedUserId;
    }
}
