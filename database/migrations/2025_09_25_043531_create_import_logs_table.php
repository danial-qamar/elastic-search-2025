<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('bill_month')->nullable();
            $table->unsignedInteger('consumers_count')->default(0);
            $table->unsignedInteger('subdivisions_count')->default(0);
            $table->unsignedInteger('indexed_count')->default(0);
            $table->unsignedInteger('duration')->nullable()->comment('Total time in seconds');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
