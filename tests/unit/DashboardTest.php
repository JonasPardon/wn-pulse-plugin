<?php

declare(strict_types=1);

namespace JonasPardon\Pulse\Tests\Unit;

use JonasPardon\Pulse\Tests\PulseTestCase;
use Livewire\Features\SupportRedirects\Redirector;

class DashboardTest extends PulseTestCase
{
    public function test_livewires_redirector_resolves_with_winters_url_generator(): void
    {
        $this->assertSame(app('url'), app(Redirector::class)->getUrlGenerator());
    }
}
