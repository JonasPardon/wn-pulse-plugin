<?php

declare(strict_types=1);

namespace JonasPardon\Pulse\Tests\Unit;

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
}
