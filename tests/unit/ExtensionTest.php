<?php

declare(strict_types=1);

namespace JonasPardon\Pulse\Tests\Unit;

use Illuminate\Support\Facades\File;
use JonasPardon\Pulse\Classes\PulseServiceProvider;
use JonasPardon\Pulse\Plugin;
use JonasPardon\Pulse\Tests\Fixtures\CardServiceProvider;
use JonasPardon\Pulse\Tests\PulseTestCase;

class ExtensionTest extends PulseTestCase
{
    public function test_configured_card_providers_are_registered(): void
    {
        require_once __DIR__ . '/../fixtures/CardServiceProvider.php';
        config(['jonaspardon.pulse::providers' => [CardServiceProvider::class]]);

        (new Plugin($this->app))->register();

        $this->assertTrue($this->app->bound('pulse-test-card'));
    }

    public function test_card_providers_that_are_not_installed_are_skipped(): void
    {
        config(['jonaspardon.pulse::providers' => ['Acme\\Missing\\PulseCardServiceProvider']]);

        (new Plugin($this->app))->register();

        $this->assertEmpty($this->app->getProviders('Acme\\Missing\\PulseCardServiceProvider'));
    }

    public function test_a_published_dashboard_replaces_pulses_own(): void
    {
        $path = sys_get_temp_dir() . '/pulse-views-' . uniqid();
        File::ensureDirectoryExists($path);
        File::put($path . '/dashboard.blade.php', 'Published dashboard');

        try {
            $this->app->getProvider(PulseServiceProvider::class)->preferPublishedViews($path);

            $this->assertSame('Published dashboard', view('pulse::dashboard')->render());
        } finally {
            File::deleteDirectory($path);
        }
    }

    public function test_pulses_own_dashboard_stays_without_a_published_one(): void
    {
        $this->app->getProvider(PulseServiceProvider::class)->preferPublishedViews('/nonexistent/pulse-views');

        $this->assertStringContainsString('pulse.servers', file_get_contents(view('pulse::dashboard')->getPath()));
    }
}
