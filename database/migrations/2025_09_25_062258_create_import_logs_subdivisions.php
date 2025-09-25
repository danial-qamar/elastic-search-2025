<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_log_subdivisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_log_id')
                  ->constrained('import_logs')
                  ->cascadeOnDelete();

            $table->string('subdivision_code');
            $table->unsignedInteger('consumers_count')->default(0);
            $table->unsignedInteger('indexed_count')->default(0);

            $table->timestamps();

            $table->unique(['import_log_id', 'subdivision_code'], 'log_subdivision_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_log_subdivisions');
    }
};
