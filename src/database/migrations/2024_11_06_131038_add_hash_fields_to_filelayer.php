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
        Schema::table('file_layer_files', function (Blueprint $table) {
            $table->string('hash')->nullable()->after('path');
            $table->string('hash_name')->nullable()->after('hash');
            $table->string('etag')->nullable()->after('hash_name');

            $table->unique('hash');
            $table->index('etag');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_layer_files', function (Blueprint $table) {
            $table->dropColumn('hash');
            $table->dropColumn('hash_name');
            $table->dropColumn('etag');
        });
    }
};
