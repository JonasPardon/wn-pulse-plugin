<?php

declare(strict_types=1);

namespace JonasPardon\Pulse\Classes;

use JonasPardon\Pulse\Classes\Recorders\SlowRequests;
use Laravel\Pulse\Contracts\ResolvesUsers;
use Laravel\Pulse\Contracts\Storage;
use Laravel\Pulse\Http\Middleware\Authorize as PulseAuthorize;
use Laravel\Pulse\Pulse;
use Laravel\Pulse\PulseServiceProvider as BasePulseServiceProvider;
use Laravel\Pulse\Recorders\SlowRequests as PulseSlowRequests;

/**
 * Pulse's provider with the Winter-aware pieces swapped in during register(),
 * so they are in place before Pulse boots and resolves them.
 */
class PulseServiceProvider extends BasePulseServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->singleton(Pulse::class, WinterPulse::class);
        $this->app->singleton(WinterUsers::class);
        $this->app->singleton(ResolvesUsers::class, fn ($app) => $app->make(WinterUsers::class));
        $this->app->bind(Storage::class, DatabaseStorage::class);

        $this->swapConfiguredClasses();
    }

    /**
     * Leaves any class the app configured itself alone.
     */
    private function swapConfiguredClasses(): void
    {
        $config = $this->app->make('config');

        $config->set('pulse.middleware', array_map(
            fn (mixed $middleware): mixed => $middleware === PulseAuthorize::class ? Authorize::class : $middleware,
            $config->get('pulse.middleware', []),
        ));

        $recorders = $config->get('pulse.recorders', []);

        if (array_key_exists(PulseSlowRequests::class, $recorders)) {
            $recorders[SlowRequests::class] = $recorders[PulseSlowRequests::class] + [
                'groups' => ['#/\d+(?=/|$)#' => '/*'],
            ];
            unset($recorders[PulseSlowRequests::class]);
        }

        $config->set('pulse.recorders', $recorders);
    }
}
