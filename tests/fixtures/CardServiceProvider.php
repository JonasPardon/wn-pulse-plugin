<?php

declare(strict_types=1);

namespace JonasPardon\Pulse\Tests\Fixtures;

use Illuminate\Support\ServiceProvider;

class CardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->instance('pulse-test-card', true);
    }
}
