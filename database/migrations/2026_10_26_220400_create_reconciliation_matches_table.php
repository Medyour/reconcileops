<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('internal_record_id')->constrained('source_records')->cascadeOnDelete();
            $table->foreignId('provider_record_id')->constrained('source_records')->cascadeOnDelete();
            $table->string('rule', 32)->index();
            $table->bigInteger('amount_difference_minor')->default(0);
            $table->integer('date_difference_days')->default(0);
            $table->text('explanation');
            $table->timestamps();

            $table->unique(
                ['reconciliation_run_id', 'internal_record_id'],
                'reconciliation_matches_internal_unique'
            );
            $table->unique(
                ['reconciliation_run_id', 'provider_record_id'],
                'reconciliation_matches_provider_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_matches');
    }
};
