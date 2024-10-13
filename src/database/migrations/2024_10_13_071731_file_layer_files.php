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
        Schema::create('file_layer_files', function (Blueprint $table) {
            $table->id();
            $table->string('storage');
            $table->string('path');
            $table->string('mime');
            $table->unsignedBigInteger('size');
            $table->string('source_name');
            $table->string('alias')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_layer_files');
    }
};
