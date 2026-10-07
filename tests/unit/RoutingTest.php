<?php

declare(strict_types=1);

namespace JonasPardon\Pulse\Tests\Unit;

use Cms\Classes\CmsController;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use JonasPardon\Pulse\Plugin;
use JonasPardon\Pulse\Tests\PulseTestCase;

class RoutingTest extends PulseTestCase
{
    public function test_routes_registered_after_the_cms_catch_all_win_once_it_is_demoted(): void
    {
        $router = new Router(new Dispatcher(), $this->app);
        $router->any('{slug?}', CmsController::class . '@run')->where('slug', '(.*)?');
        $router->get('pulse', fn (): string => 'pulse');

        $this->assertSame('{slug?}', $router->getRoutes()->match(Request::create('/pulse'))->uri());

        Plugin::demoteCmsCatchAll($router);

        $this->assertSame('pulse', $router->getRoutes()->match(Request::create('/pulse'))->uri());
        $this->assertSame('{slug?}', $router->getRoutes()->match(Request::create('/some/page'))->uri());
    }

    public function test_other_catch_alls_are_left_alone(): void
    {
        $router = new Router(new Dispatcher(), $this->app);
        $route = $router->any('{slug?}', fn (): string => 'other')->where('slug', '(.*)?');

        Plugin::demoteCmsCatchAll($router);

        $this->assertFalse($route->isFallback);
    }
}
