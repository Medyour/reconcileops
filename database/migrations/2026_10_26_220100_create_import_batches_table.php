<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('data_source_id')->constrained()->cascadeOnDelete();
            $table->string('source_filename');
            $table->char('checksum', 64);
            $table->string('status', 24)->index();
            $table->unsignedInteger('record_count')->default(0);
            $table->unsignedInteger('rejected_count')->default(0);
            $table->timestamp('imported_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['data_source_id', 'checksum'], 'import_batches_source_checksum_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
