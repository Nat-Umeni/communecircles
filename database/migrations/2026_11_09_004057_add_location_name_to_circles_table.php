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
        // What a circle calls its own place, e.g. "Fernhill Farm", near a GeoNames town or on its own.
        Schema::table('circles', function (Blueprint $table) {
            $table->string('location_name')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('circles', function (Blueprint $table) {
            $table->dropColumn('location_name');
        });
    }
};
