<?php

declare(strict_types=1);

namespace JonasPardon\Pulse\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use JonasPardon\Pulse\Classes\Recorders\SlowRequests;
use JonasPardon\Pulse\Tests\PulseTestCase;
use ReflectionMethod;

class SlowRequestsTest extends PulseTestCase
{
    public function test_catch_all_routes_are_keyed_by_path_with_ids_grouped(): void
    {
        $this->assertSame('/en/account/orders/*', $this->path('{slug?}', 'en/account/orders/1234'));
        $this->assertSame('/backend/acme/blog/posts/update/*', $this->path('backend/{slug?}', 'backend/acme/blog/posts/update/12'));
        $this->assertSame('/en/about-us', $this->path('{slug?}', 'en/about-us'));
    }

    public function test_numbers_inside_a_segment_are_kept(): void
    {
        $this->assertSame('/en/summer-2024', $this->path('{slug?}', 'en/summer-2024'));
    }

    public function test_winter_user_reset_and_activation_codes_are_folded(): void
    {
        $code = '12!' . str_repeat('aB3', 14);

        $this->assertSame('/en/account/*', $this->path('{slug?}', 'en/account/' . $code));
        $this->assertSame('/en/account/*', $this->path('{slug?}', 'en/account/' . str_replace('!', '%21', $code)));
    }

    public function test_token_like_segments_are_folded(): void
    {
        $this->assertSame('/en/pay/*', $this->path('{slug?}', 'en/pay/' . str_repeat('f00d', 10)));
    }

    public function test_long_readable_slugs_are_kept(): void
    {
        $slug = 'en/products/a-very-long-product-slug-with-many-hyphenated-words';

        $this->assertSame('/' . $slug, $this->path('{slug?}', $slug));
    }

    public function test_dedicated_routes_keep_their_uri(): void
    {
        $this->assertSame('/resizer/{identifier}/{encodedUrl}', $this->path('resizer/{identifier}/{encodedUrl}', 'resizer/abc/def'));
    }

    private function path(string $uri, string $path): string
    {
        $request = Request::create('/' . $path);
        $route = (new Route('GET', $uri, fn () => null))->bind($request);
        $request->setRouteResolver(fn (): Route => $route);

        return (new ReflectionMethod(SlowRequests::class, 'resolveRoutePath'))
            ->invoke(app(SlowRequests::class), $request)[0];
    }
}
