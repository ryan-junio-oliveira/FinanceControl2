<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class BackupTest extends TestCase
{
    public function test_backup_skips_gracefully_on_sqlite(): void
    {
        Config::set('database.default', 'sqlite');

        $this->artisan('db:backup')->assertSuccessful();
    }

    public function test_restore_refuses_on_sqlite(): void
    {
        Config::set('database.default', 'sqlite');

        $this->artisan('db:restore', ['file' => 'inexistente.sql.gz'])->assertFailed();
    }

    public function test_backup_command_is_scheduled_daily(): void
    {
        $events = collect(app(Schedule::class)->events());
        $this->assertTrue(
            $events->contains(fn ($e) => str_contains((string) $e->command, 'db:backup')),
            'db:backup deve estar agendado em routes/console.php'
        );
    }
}
