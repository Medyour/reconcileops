<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_runs', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('internal_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->foreignId('provider_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->string('status', 24)->index();
            $table->unsignedInteger('exact_matches_count')->default(0);
            $table->unsignedInteger('tolerant_matches_count')->default(0);
            $table->unsignedInteger('exceptions_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(
                ['internal_batch_id', 'provider_batch_id'],
                'reconciliation_runs_batch_pair_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_runs');
    }
};
