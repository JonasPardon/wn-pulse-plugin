<?php

declare(strict_types=1);

namespace JonasPardon\Pulse\Tests\Unit;

use JonasPardon\Pulse\Classes\Authorize;
use JonasPardon\Pulse\Classes\DatabaseStorage;
use JonasPardon\Pulse\Classes\Recorders\SlowRequests;
use JonasPardon\Pulse\Classes\WinterPulse;
use JonasPardon\Pulse\Classes\WinterUsers;
use JonasPardon\Pulse\Tests\PulseTestCase;
use Laravel\Pulse\Contracts\ResolvesUsers;
use Laravel\Pulse\Contracts\Storage;
use Laravel\Pulse\Http\Middleware\Authorize as PulseAuthorize;
use Laravel\Pulse\Pulse;
use Laravel\Pulse\Recorders\SlowRequests as PulseSlowRequests;

class ConfigTest extends PulseTestCase
{
    public function test_pulse_classes_are_swapped_for_the_winter_aware_ones(): void
    {
        $this->assertContains(Authorize::class, config('pulse.middleware'));
        $this->assertNotContains(PulseAuthorize::class, config('pulse.middleware'));

        $this->assertArrayHasKey(SlowRequests::class, config('pulse.recorders'));
        $this->assertArrayNotHasKey(PulseSlowRequests::class, config('pulse.recorders'));
        $this->assertSame(1000, (int) config('pulse.recorders.' . SlowRequests::class . '.threshold'));
    }

    public function test_bindings_point_at_the_winter_implementations(): void
    {
        $this->assertInstanceOf(WinterPulse::class, app(Pulse::class));
        $this->assertInstanceOf(WinterUsers::class, app(ResolvesUsers::class));
        $this->assertInstanceOf(DatabaseStorage::class, app(Storage::class));
    }
}
