<?php

declare(strict_types=1);

namespace JonasPardon\Pulse\Tests\Unit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use JonasPardon\Pulse\Tests\PulseTestCase;
use Laravel\Pulse\Facades\Pulse;
use RuntimeException;

class RecordingTest extends PulseTestCase
{
    public function test_entries_are_stored_with_a_php_computed_key_hash(): void
    {
        Pulse::record('jonaspardon_pulse_test', 'some-key', 1)->count();
        Pulse::ingest();

        $this->assertSame(
            md5('some-key'),
            DB::table('pulse_entries')->where('type', 'jonaspardon_pulse_test')->value('key_hash'),
        );
    }

    public function test_exceptions_reported_through_winter_are_recorded(): void
    {
        Event::dispatch('exception.report', [new RuntimeException('reported through Winter')]);
        Pulse::ingest();

        $this->assertTrue(
            DB::table('pulse_entries')
                ->where('type', 'exception')
                ->where('key', 'like', '%RuntimeException%')
                ->exists(),
        );
    }
}
