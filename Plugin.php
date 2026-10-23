<?php

declare(strict_types=1);

namespace JonasPardon\Pulse;

use Backend\Facades\BackendAuth;
use Cms\Classes\CmsController;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use JonasPardon\Pulse\Classes\PulseServiceProvider;
use Laravel\Pulse\Facades\Pulse as PulseFacade;
use Livewire\Features\SupportRedirects\Redirector;
use Livewire\LivewireServiceProvider;
use System\Classes\PluginBase;
use System\Classes\SettingsManager;
use Throwable;

class Plugin extends PluginBase
{
    public const PERMISSION = 'jonaspardon.pulse.access_dashboard';

    public function pluginDetails(): array
    {
        return [
            'name' => 'jonaspardon.pulse::lang.plugin.name',
            'description' => 'jonaspardon.pulse::lang.plugin.description',
            'author' => 'Jonas Pardon',
            'icon' => 'icon-heartbeat',
            'homepage' => 'https://github.com/JonasPardon/wn-pulse-plugin',
        ];
    }

    /**
     * Winter disables Laravel's package discovery, so the providers are
     * registered here rather than through composer.json "extra.laravel".
     */
    public function register(): void
    {
        $this->app->register(LivewireServiceProvider::class);
        $this->app->register(PulseServiceProvider::class);
        $this->registerCardProviders();

        // Livewire autowires its Redirector, which needs the concrete UrlGenerator
        // that Winter doesn't alias to `url`. Laravel builds its own the same way.
        $this->app->bind(Redirector::class, fn ($app): Redirector => new Redirector($app['url']));

        Event::listen('cms.route', function (): void {
            static::demoteCmsCatchAll($this->app->make(Router::class));
        });
    }

    public function boot(): void
    {
        // The nullable parameter is what lets Gate call this for a guest at all.
        Gate::define('viewPulse', function (?object $guest = null): bool {
            $user = BackendAuth::getUser();

            return $user !== null && ($user->isSuperUser() || $user->hasAccess(self::PERMISSION));
        });

        // Winter's exception handler overrides report() without running Laravel's
        // reportable() callbacks, which is where Pulse listens.
        Event::listen('exception.report', function (Throwable $exception): void {
            PulseFacade::report($exception);
        });
    }

    public function registerPermissions(): array
    {
        return [
            self::PERMISSION => [
                'label' => 'jonaspardon.pulse::lang.permissions.access_dashboard',
                'tab' => 'jonaspardon.pulse::lang.plugin.name',
            ],
        ];
    }

    public function registerSettings(): array
    {
        return [
            'dashboard' => [
                'label' => 'jonaspardon.pulse::lang.settings.label',
                'description' => 'jonaspardon.pulse::lang.settings.description',
                'category' => SettingsManager::CATEGORY_LOGS,
                'icon' => 'icon-heartbeat',
                'url' => url(config('pulse.path', 'pulse')),
                'permissions' => [self::PERMISSION],
                'order' => 600,
            ],
        ];
    }

    /**
     * Pulse card packages ship a provider for package discovery, which Winter
     * turns off. A provider whose package isn't installed yet is skipped, so a
     * deploy that lands the config before `composer install` keeps working.
     */
    private function registerCardProviders(): void
    {
        foreach ((array) Config::get('jonaspardon.pulse::providers', []) as $provider) {
            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }

    /**
     * The CMS module answers every URL with a `{slug?}` catch-all, and the first
     * matching route wins. Marked as a fallback it is tried last instead, so the
     * /pulse and Livewire routes registered after it are reachable.
     */
    public static function demoteCmsCatchAll(Router $router): void
    {
        foreach ($router->getRoutes()->getRoutes() as $route) {
            if (static::isCmsCatchAll($route)) {
                $route->fallback();
            }
        }
    }

    private static function isCmsCatchAll(Route $route): bool
    {
        return $route->uri() === '{slug?}' && $route->getActionName() === CmsController::class . '@run';
    }
}
