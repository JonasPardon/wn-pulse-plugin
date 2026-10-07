<?php

declare(strict_types=1);

namespace JonasPardon\Pulse\Tests\Unit;

use Illuminate\Support\Facades\Gate;
use JonasPardon\Pulse\Plugin;
use JonasPardon\Pulse\Tests\PulseTestCase;

class AccessTest extends PulseTestCase
{
    public function test_guests_cannot_view_pulse(): void
    {
        $this->assertFalse(Gate::allows('viewPulse'));
    }

    public function test_backend_users_without_the_permission_cannot_view_pulse(): void
    {
        $this->loginBackendUser();

        $this->assertFalse(Gate::allows('viewPulse'));
    }

    public function test_backend_users_with_the_permission_can_view_pulse(): void
    {
        $this->loginBackendUser([Plugin::PERMISSION => 1]);

        $this->assertTrue(Gate::allows('viewPulse'));
    }

    public function test_superusers_can_view_pulse(): void
    {
        $this->loginBackendUser(superUser: true);

        $this->assertTrue(Gate::allows('viewPulse'));
    }

    public function test_guests_get_a_403_rather_than_winters_500(): void
    {
        $this->get('/pulse')->assertStatus(403);
    }

    public function test_the_permission_is_registered(): void
    {
        $this->assertArrayHasKey(Plugin::PERMISSION, (new Plugin($this->app))->registerPermissions());
    }
}
