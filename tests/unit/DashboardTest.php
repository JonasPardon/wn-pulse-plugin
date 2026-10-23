<?php

declare(strict_types=1);

namespace JonasPardon\Pulse\Tests\Unit;

use Cms\Classes\CmsController;
use JonasPardon\Pulse\Tests\PulseTestCase;
use Laravel\Pulse\Facades\Pulse;
use Laravel\Pulse\Livewire\SlowRequests as SlowRequestsCard;
use Livewire\Features\SupportRedirects\Redirector;
use Livewire\Livewire;

class DashboardTest extends PulseTestCase
{
    public function test_the_slow_requests_card_renders_recorded_requests(): void
    {
        Pulse::record('slow_request', json_encode(['GET', '/en/about-us', CmsController::class . '@run']), 1500)->max()->count();
        Pulse::ingest();

        Livewire::withoutLazyLoading()->test(SlowRequestsCard::class)->assertSee('/en/about-us');
    }

    public function test_livewires_redirector_resolves_with_winters_url_generator(): void
    {
        $this->assertSame(app('url'), app(Redirector::class)->getUrlGenerator());
    }
}
