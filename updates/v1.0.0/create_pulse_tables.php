<?php

declare(strict_types=1);

namespace JonasPardon\Pulse\Updates\v1_0_0;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Winter\Storm\Database\Updates\Migration;

/**
 * Pulse's own migration, except that on MySQL `key_hash` is a plain column
 * filled by JonasPardon\Pulse\Classes\DatabaseStorage, because MySQL 9.6 no
 * longer allows md5() in a generated column (laravel/pulse#495).
 */
class CreatePulseTables extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection($this->connectionName());

        $schema->create('pulse_values', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('timestamp');
            $table->string('type');
            $table->mediumText('key');
            $this->keyHash($table);
            $table->mediumText('value');

            $table->index('timestamp');
            $table->index('type');
            $table->unique(['type', 'key_hash']);
        });

        $schema->create('pulse_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('timestamp');
            $table->string('type');
            $table->mediumText('key');
            $this->keyHash($table);
            $table->bigInteger('value')->nullable();

            $table->index('timestamp');
            $table->index('type');
            $table->index('key_hash');
            $table->index(['timestamp', 'type', 'key_hash', 'value']);
        });

        $schema->create('pulse_aggregates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('bucket');
            $table->unsignedMediumInteger('period');
            $table->string('type');
            $table->mediumText('key');
            $this->keyHash($table);
            $table->string('aggregate');
            $table->decimal('value', 20, 2);
            $table->unsignedInteger('count')->nullable();

            $table->unique(['bucket', 'period', 'type', 'aggregate', 'key_hash']);
            $table->index(['period', 'bucket']);
            $table->index('type');
            $table->index(['period', 'type', 'aggregate', 'bucket']);
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connectionName());

        $schema->dropIfExists('pulse_values');
        $schema->dropIfExists('pulse_entries');
        $schema->dropIfExists('pulse_aggregates');
    }

    private function keyHash(Blueprint $table): void
    {
        match (DB::connection($this->connectionName())->getDriverName()) {
            'mariadb' => $table->char('key_hash', 16)->charset('binary')->virtualAs('unhex(md5(`key`))'),
            'mysql' => $table->char('key_hash', 32),
            'pgsql' => $table->uuid('key_hash')->storedAs('md5("key")::uuid'),
            'sqlite' => $table->string('key_hash'),
        };
    }

    private function connectionName(): ?string
    {
        return Config::get('pulse.storage.database.connection');
    }
}
