<?php

namespace Tests\Feature\Http;

use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_displays_a_clear_empty_state(): void
    {
        $response = $this->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSeeText('Chaque rapprochement reste explicable.')
            ->assertSeeText('Aucun rapprochement terminé')
            ->assertSeeText('Démonstration publique en lecture seule');
    }

    public function test_dashboard_summarizes_the_latest_demo_run(): void
    {
        $this->seed(DemoDataSeeder::class);

        $response = $this->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSeeText('19')
            ->assertSeeText('11,1 %')
            ->assertSeeText('5,00 €')
            ->assertSeeText('Journal interne')
            ->assertSeeText('Relevé prestataire')
            ->assertSeeText('Examiner le résultat');
    }
}
