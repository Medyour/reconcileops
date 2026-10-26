<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('source_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->string('record_key', 128);
            $table->string('external_reference', 128)->nullable()->index();
            $table->string('secondary_reference', 128)->nullable()->index();
            $table->date('occurred_on');
            $table->bigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('normalized_reference', 128)->nullable()->index();
            $table->char('fingerprint', 64)->index();
            $table->json('raw_payload');
            $table->timestamps();

            $table->unique(['import_batch_id', 'record_key'], 'source_records_batch_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_records');
    }
};
