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
        Schema::create('import_reports', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('checksum', 128);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed']);
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('imported')->default(0);
            $table->unsignedInteger('updated')->default(0);
            $table->unsignedInteger('invalid')->default(0);
            $table->unsignedInteger('duplicates')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_reports');
    }
};
