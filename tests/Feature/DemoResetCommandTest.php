<?php

namespace Tests\Feature;

use App\Models\DataSource;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoResetCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_reset_is_disabled_by_default(): void
    {
        config()->set('reconcileops.demo_mode', false);

        $this->artisan('reconcileops:demo-reset')
            ->expectsOutputToContain('Demo reset is disabled.')
            ->assertFailed();
    }

    public function test_production_reset_requires_force_and_exclusive_options(): void
    {
        app()->instance('env', 'production');
        config()->set('reconcileops.demo_mode', true);

        $this->artisan('reconcileops:demo-reset', ['--force' => true])
            ->expectsOutputToContain('both --force and --exclusive')
            ->assertFailed();
    }

    public function test_exclusive_reset_refuses_user_accounts_and_preserves_existing_demo_data(): void
    {
        config()->set('reconcileops.demo_mode', true);
        $this->seed();
        User::factory()->create();

        $this->artisan('reconcileops:demo-reset', ['--exclusive' => true])
            ->expectsOutputToContain('user accounts exist')
            ->assertFailed();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('source_records', 19);
    }

    public function test_exclusive_reset_refuses_unknown_data_sources(): void
    {
        config()->set('reconcileops.demo_mode', true);
        DataSource::factory()->create(['slug' => 'customer-ledger']);

        $this->artisan('reconcileops:demo-reset', ['--exclusive' => true])
            ->expectsOutputToContain('unknown data source exists')
            ->assertFailed();

        $this->assertDatabaseHas('data_sources', ['slug' => 'customer-ledger']);
    }

    public function test_reset_clears_operational_state_and_restores_the_exact_demo_twice(): void
    {
        config()->set('reconcileops.demo_mode', true);
        $this->seed();
        DB::table('sessions')->insert([
            'id' => 'demo-session',
            'user_id' => null,
            'ip_address' => null,
            'user_agent' => null,
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        foreach ([1, 2] as $run) {
            $this->artisan('reconcileops:demo-reset', ['--exclusive' => true])
                ->expectsOutputToContain('2 sources, 2 batches, 19 records, 1 run, 2 matches, 8 exceptions and 1 decision')
                ->assertSuccessful();

            $this->assertDatabaseCount('data_sources', 2);
            $this->assertDatabaseCount('import_batches', 2);
            $this->assertDatabaseCount('source_records', 19);
            $this->assertDatabaseCount('reconciliation_runs', 1);
            $this->assertDatabaseCount('reconciliation_matches', 2);
            $this->assertDatabaseCount('reconciliation_exceptions', 8);
            $this->assertDatabaseCount('reconciliation_decisions', 1);
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('sessions', 0);
            $this->assertSame($run, $run);
        }
    }
}
