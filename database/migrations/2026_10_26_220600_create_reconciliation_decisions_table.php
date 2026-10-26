<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_exception_id')->constrained()->cascadeOnDelete();
            $table->string('action', 32)->index();
            $table->string('decided_by');
            $table->text('explanation');
            $table->timestamp('decided_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_decisions');
    }
};
