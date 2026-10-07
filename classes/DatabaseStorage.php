<?php

declare(strict_types=1);

namespace JonasPardon\Pulse\Classes;

use Laravel\Pulse\Storage\DatabaseStorage as PulseDatabaseStorage;

/**
 * Pairs with the v1.0.0 migration: on MySQL `key_hash` is a plain column
 * filled from PHP, because MySQL 9.6 no longer allows md5() in a generated
 * column. Same approach as the upstream fix in laravel/pulse#495.
 */
class DatabaseStorage extends PulseDatabaseStorage
{
    protected function requiresManualKeyHash(): bool
    {
        return in_array($this->connection()->getDriverName(), ['mysql', 'sqlite'], true);
    }
}
