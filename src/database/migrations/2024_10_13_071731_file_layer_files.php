<?php

declare(strict_types=1);

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
            $table->string('directory')->nullable();
            $table->string('mime');
            $table->unsignedBigInteger('size');
            $table->timestamp('last_modified');
            $table->string('source')->nullable();
            $table->string('alias')->nullable();
            $table->string('url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('path');
            $table->unique('source');
            $table->unique('alias');
            $table->index(['directory', 'storage']);
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
