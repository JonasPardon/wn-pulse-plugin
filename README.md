# Pulse for Winter CMS

[Laravel Pulse](https://pulse.laravel.com) for Winter CMS 1.3+. Install it and `/pulse` works: the routes, the backend access, the users and the exceptions are all wired up for Winter.

## Installation

```bash
composer require jonaspardon/wn-pulse-plugin
php artisan winter:up
```

Then grant the **Access the Pulse performance dashboard** permission to the backend roles that should see it; superusers always can. The dashboard is at `/pulse`, and linked from *Settings → Logs → Pulse* in the backend.

Don't register `Laravel\Pulse\PulseServiceProvider` yourself: the plugin registers its own subclass of it, and Livewire's provider too.

## What the plugin handles

| Winter behaviour | What breaks in plain Pulse | What the plugin does |
|---|---|---|
| Package discovery is disabled | Pulse and Livewire never load | Registers both providers |
| The CMS `{slug?}` catch-all route is registered first and wins | `/pulse` and the Livewire endpoints render a CMS 404 | Marks the catch-all as a fallback route, so it is tried last |
| No Laravel `auth` binding | The usage card and per-user recorders silently record nothing | Attributes work to Winter.User frontend users and backend users |
| Gate has no user resolver; the dashboard is only for `local` by default | Nobody can open the dashboard on a deployed site | `viewPulse` gate backed by the backend session and a permission |
| The error page renders an `AuthorizationException` as a 500 | Guests get a 500 instead of a 403 | Middleware that aborts with a 403 |
| The exception handler overrides `report()` without running `reportable()` callbacks | The exceptions card stays empty | Forwards Winter's `exception.report` event to Pulse |
| Every CMS and backend page shares one catch-all route | Slow requests collapse into `/{slug?}` | Keys catch-all requests by path, with numeric segments grouped as `*` |
| `winter:up` doesn't run package migrations | No tables | Ships the Pulse tables as a plugin migration |

## Configuration

Pulse's own configuration applies as usual. Publish it with `php artisan vendor:publish --tag=pulse-config` if you need to change it. The plugin swaps Pulse's `Authorize` middleware and `SlowRequests` recorder for its own versions only where the config still names Pulse's classes. Slow requests also accept a `groups` option (regex => replacement) for folding paths.

Which signed-in user a request is attributed to is set in `config/config.php`, and can be overridden from the app as `config/jonaspardon/pulse/config.php`:

```php
'users' => ['frontend', 'backend'], // checked in this order; [] turns attribution off
```

User keys are stored with their source (`frontend:12`, `backend:3`), because the two tables share ids. Frontend users need the Winter.User plugin, and the source is skipped without it.

## MySQL 9.6

MySQL 9.6 no longer allows `md5()` in generated columns, which Pulse's own migration relies on. On MySQL this plugin creates `key_hash` as a plain column and fills it from PHP, the same approach as the upstream fix in [laravel/pulse#495](https://github.com/laravel/pulse/pull/495). This works on MySQL 8 too. MariaDB, PostgreSQL and SQLite keep Pulse's own schema.

If you already ran Pulse's own migration on MySQL, drop the `pulse_*` tables before running `winter:up`. The migration fails on existing tables rather than adopting a schema it can't write to.

## Production

- **Servers card:** run `php artisan pulse:check` as a daemon on every server.
- **Ingest:** by default, entries are written to the database at the end of each request. On busy sites, set `PULSE_INGEST_DRIVER=redis` and run `php artisan pulse:work` as a daemon.
- **Deploys:** run `php artisan pulse:restart` on deploy, so the daemons pick up new code.

## Tests

From the root of a Winter app that has the plugin installed:

```bash
./vendor/bin/phpunit --configuration=plugins/jonaspardon/pulse/phpunit.xml --bootstrap=./modules/system/tests/bootstrap/app.php
```

The tests use the app's test database; the Pulse tables are created there on first run.
