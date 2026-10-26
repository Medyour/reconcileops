<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('internal_record_id')->nullable()->constrained('source_records')->cascadeOnDelete();
            $table->foreignId('provider_record_id')->nullable()->constrained('source_records')->cascadeOnDelete();
            $table->string('type', 32)->index();
            $table->string('status', 24)->index();
            $table->text('explanation');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_exceptions');
    }
};
