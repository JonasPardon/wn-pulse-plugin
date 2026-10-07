<?php

declare(strict_types=1);

namespace JonasPardon\Pulse\Tests;

use Backend\Facades\BackendAuth;
use Backend\Models\User as BackendUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use JonasPardon\Pulse\Plugin;
use JonasPardon\Pulse\Updates\v1_0_0\CreatePulseTables;
use System\Tests\Bootstrap\TestCase;

/**
 * Winter's test bootstrap loads plugins from tests/fixtures, so the plugin is
 * registered and booted by hand, the way PluginManager would.
 */
abstract class PulseTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // DDL commits implicitly, so this has to run before the test transaction.
        if (!Schema::hasTable('pulse_entries')) {
            require_once __DIR__ . '/../updates/v1.0.0/create_pulse_tables.php';
            (new CreatePulseTables())->up();
        }

        $this->app['config']->set('pulse.enabled', true);
        Config::package('jonaspardon.pulse', __DIR__ . '/../config', 'jonaspardon.pulse');

        $plugin = new Plugin($this->app);
        $plugin->register();
        $plugin->boot();

        // The app has already fired `cms.route`, so run the listener's work here.
        Plugin::demoteCmsCatchAll($this->app->make(Router::class));
    }

    protected function tearDown(): void
    {
        BackendAuth::logout();
        parent::tearDown();
    }

    /**
     * @param array<string, int> $permissions
     */
    protected function loginBackendUser(array $permissions = [], bool $superUser = false): BackendUser
    {
        $roleId = DB::table('backend_user_roles')->insertGetId([
            'name' => 'Pulse test ' . uniqid(),
            'code' => 'pulse-test-' . uniqid(),
            'permissions' => json_encode($permissions),
        ]);
        $userId = DB::table('backend_users')->insertGetId([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'login' => 'pulse-' . uniqid(),
            'email' => uniqid() . '@example.com',
            'password' => Hash::make('password'),
            'role_id' => $roleId,
            'is_activated' => 1,
            'is_superuser' => $superUser,
        ]);

        $user = BackendUser::findOrFail($userId);
        BackendAuth::login($user);

        return $user;
    }
}
