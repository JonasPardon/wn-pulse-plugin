<?php

declare(strict_types=1);

namespace JonasPardon\Pulse\Classes;

use Backend\Facades\BackendAuth;
use Backend\Models\User as BackendUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Laravel\Pulse\Contracts\ResolvesUsers;
use Winter\User\Models\User as FrontendUser;

/**
 * Keys are prefixed with their source ("backend:12", "frontend:12") because
 * backend and frontend users live in separate tables with overlapping ids.
 */
class WinterUsers implements ResolvesUsers
{
    public const BACKEND = 'backend';
    public const FRONTEND = 'frontend';

    /** @var array<string, Authenticatable> */
    private array $resolved = [];

    /**
     * The first signed-in user, checked in the order of the `users` config.
     */
    public function currentKey(): ?string
    {
        foreach ($this->sources() as $source) {
            $user = $this->authenticatedUser($source);

            if ($user !== null) {
                return $this->key($user);
            }
        }

        return null;
    }

    public function key(Authenticatable $user): ?string
    {
        $source = $this->sourceOf($user);

        return $source === null ? null : $source . ':' . $user->getAuthIdentifier();
    }

    /**
     * @param Collection<int, int|string|null> $keys
     */
    public function load(Collection $keys): self
    {
        $this->resolved = [];

        $keys->filter()
            ->map(fn (int|string $key): array => explode(':', (string) $key, 2))
            ->filter(fn (array $parts): bool => count($parts) === 2)
            ->groupBy(fn (array $parts): string => $parts[0])
            ->each(function (Collection $parts, string $source): void {
                $model = $this->modelFor($source);

                if ($model === null) {
                    return;
                }

                foreach ($model::findMany($parts->pluck(1)->all()) as $user) {
                    $this->resolved[$this->key($user)] = $user;
                }
            });

        return $this;
    }

    /**
     * @return object{name: string, extra: string, avatar: string}
     */
    public function find(int|string|null $key): object
    {
        $user = $this->resolved[(string) $key] ?? null;

        return match (true) {
            $user instanceof BackendUser => (object) [
                'name' => $user->full_name ?: $user->login,
                'extra' => $user->email,
                'avatar' => $user->getAvatarThumb(64),
            ],
            $user !== null => (object) [
                'name' => trim($user->name . ' ' . $user->surname) ?: $user->email,
                'extra' => $user->email,
                'avatar' => $user->getAvatarThumb(64),
            ],
            default => (object) [
                'name' => "ID: $key",
                'extra' => '',
                'avatar' => 'https://gravatar.com/avatar?d=mp',
            ],
        };
    }

    /**
     * @return list<string>
     */
    private function sources(): array
    {
        return array_values(array_filter(
            (array) Config::get('jonaspardon.pulse::users', [self::FRONTEND, self::BACKEND]),
            fn (string $source): bool => $this->modelFor($source) !== null,
        ));
    }

    private function authenticatedUser(string $source): ?Authenticatable
    {
        return match ($source) {
            self::BACKEND => BackendAuth::getUser(),
            self::FRONTEND => app('user.auth')->getUser(),
            default => null,
        };
    }

    private function sourceOf(Authenticatable $user): ?string
    {
        return match (true) {
            $user instanceof BackendUser => self::BACKEND,
            $this->frontendUsersAvailable() && $user instanceof FrontendUser => self::FRONTEND,
            default => null,
        };
    }

    /**
     * @return class-string|null
     */
    private function modelFor(string $source): ?string
    {
        return match (true) {
            $source === self::BACKEND => BackendUser::class,
            $source === self::FRONTEND && $this->frontendUsersAvailable() => FrontendUser::class,
            default => null,
        };
    }

    private function frontendUsersAvailable(): bool
    {
        return class_exists(FrontendUser::class) && app()->bound('user.auth');
    }
}
