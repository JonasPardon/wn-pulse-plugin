<?php

declare(strict_types=1);

namespace JonasPardon\Pulse\Classes\Recorders;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Pulse\Recorders\Concerns\Groups;
use Laravel\Pulse\Recorders\SlowRequests as PulseSlowRequests;

/**
 * Every CMS page and every backend page is served by a `{slug?}` catch-all
 * route, and Pulse keys slow requests by route URI, so out of the box they
 * would all collapse into two rows. Key those by the requested path instead,
 * folded through the `groups` config so ids don't make every row unique.
 */
class SlowRequests extends PulseSlowRequests
{
    use Groups;

    /**
     * @return array{0: string, 1: ?string}
     */
    protected function resolveRoutePath(Request $request): array
    {
        [$path, $via] = parent::resolveRoutePath($request);

        if (!Str::endsWith($request->route()->uri(), '{slug?}')) {
            return [$path, $via];
        }

        return [$this->group(Str::start($request->path(), '/')), $via];
    }
}
