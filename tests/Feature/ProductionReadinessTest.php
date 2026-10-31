<?php

namespace Tests\Feature;

use App\Actions\Demo\ProductionReadiness;
use Illuminate\Support\Facades\Config;
use LogicException;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    public function test_web_component_accepts_the_complete_fail_closed_configuration(): void
    {
        $this->configureProduction();

        app(ProductionReadiness::class)->check(web: true);

        $this->addToAssertionCount(1);
    }

    public function test_web_component_refuses_a_missing_application_key(): void
    {
        $this->configureProduction();
        Config::set('app.key');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('APP_KEY');

        app(ProductionReadiness::class)->check(web: true);
    }

    public function test_production_refuses_local_demo_decisions_even_when_other_settings_are_valid(): void
    {
        $this->configureProduction();
        Config::set('reconcileops.allow_demo_decisions', true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('RECONCILEOPS_ALLOW_DEMO_DECISIONS');

        app(ProductionReadiness::class)->check();
    }

    public function test_database_url_must_be_a_complete_postgresql_url(): void
    {
        $this->configureProduction();
        Config::set('database.connections.pgsql.url', 'not-a-connection-url');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('DATABASE_URL');

        app(ProductionReadiness::class)->check();
    }

    private function configureProduction(): void
    {
        app()->instance('env', 'production');

        Config::set([
            'app.debug' => false,
            'app.url' => 'https://reconcileops.mkirchel.com',
            'app.locale' => 'fr',
            'app.fallback_locale' => 'fr',
            'app.key' => 'base64:'.base64_encode(str_repeat('k', 32)),
            'app.cipher' => 'AES-256-CBC',
            'reconcileops.demo_mode' => true,
            'reconcileops.allow_demo_decisions' => false,
            'database.default' => 'pgsql',
            'database.connections.pgsql.url' => sprintf(
                '%s://%s:%s@%s/%s',
                'postgresql',
                'reconcileops',
                'password',
                'private-db:25060',
                'reconcileops?sslmode=require',
            ),
            'cache.default' => 'database',
            'session.driver' => 'database',
            'session.encrypt' => true,
            'session.secure' => true,
            'session.http_only' => true,
            'session.same_site' => 'lax',
            'queue.default' => 'sync',
            'logging.default' => 'stderr',
            'logging.channels.stderr.level' => 'warning',
            'mail.default' => 'array',
        ]);
    }
}
