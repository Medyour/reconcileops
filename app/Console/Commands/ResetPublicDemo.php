<?php

namespace App\Console\Commands;

use App\Actions\Demo\ResetDemo;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use LogicException;
use Throwable;

#[Signature('reconcileops:demo-reset {--force : Confirm a destructive demo reset} {--exclusive : Refuse unknown records before resetting}')]
#[Description('Restore the deterministic public demonstration without creating accounts or credentials')]
class ResetPublicDemo extends Command
{
    public function handle(ResetDemo $resetDemo): int
    {
        if (! config('reconcileops.demo_mode')) {
            $this->components->error('Demo reset is disabled.');

            return self::FAILURE;
        }

        if (app()->isProduction() && (! $this->option('force') || ! $this->option('exclusive'))) {
            $this->components->error('Production resets require both --force and --exclusive.');

            return self::FAILURE;
        }

        try {
            $summary = $resetDemo->execute(exclusive: (bool) $this->option('exclusive'));
        } catch (LogicException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable) {
            $this->components->error('Demo reset failed without exposing database details.');

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Demo restored: %d sources, %d batches, %d records, %d run, %d matches, %d exceptions and %d decision. No account was created.',
            $summary['sources'],
            $summary['batches'],
            $summary['records'],
            $summary['runs'],
            $summary['matches'],
            $summary['exceptions'],
            $summary['decisions'],
        ));

        return self::SUCCESS;
    }
}
