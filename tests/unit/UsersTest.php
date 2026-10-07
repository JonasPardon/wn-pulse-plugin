<?php

declare(strict_types=1);

namespace JonasPardon\Pulse\Tests\Unit;

use JonasPardon\Pulse\Classes\WinterUsers;
use JonasPardon\Pulse\Tests\PulseTestCase;
use Laravel\Pulse\Facades\Pulse;

class UsersTest extends PulseTestCase
{
    public function test_nobody_is_attributed_without_a_signed_in_user(): void
    {
        $this->assertNull(Pulse::resolveAuthenticatedUserId());
    }

    public function test_backend_users_are_attributed_with_a_prefixed_key(): void
    {
        $user = $this->loginBackendUser();

        $this->assertSame('backend:' . $user->id, Pulse::resolveAuthenticatedUserId());
    }

    public function test_attribution_can_be_turned_off(): void
    {
        config(['jonaspardon.pulse::users' => []]);
        $this->loginBackendUser();

        $this->assertNull(Pulse::resolveAuthenticatedUserId());
    }

    public function test_loaded_users_are_shown_by_name_and_email(): void
    {
        $user = $this->loginBackendUser();
        $users = app(WinterUsers::class)->load(collect(['backend:' . $user->id]));

        $found = $users->find('backend:' . $user->id);

        $this->assertSame('Ada Lovelace', $found->name);
        $this->assertSame($user->email, $found->extra);
    }

    public function test_unknown_keys_fall_back_to_the_id(): void
    {
        $users = app(WinterUsers::class)->load(collect(['backend:999999999', 'frontend:1', 'nonsense', null]));

        $this->assertSame('ID: backend:999999999', $users->find('backend:999999999')->name);
        $this->assertSame('ID: nonsense', $users->find('nonsense')->name);
    }
}
