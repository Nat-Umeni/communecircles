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
        // A circle's optional location: a town or village from GeoNames, copied here when it's chosen.
        Schema::table('circles', function (Blueprint $table) {
            $table->unsignedBigInteger('geonames_id')->nullable()->after('description');
            $table->string('place_name')->nullable()->after('geonames_id');
            $table->string('region')->nullable()->after('place_name');
            $table->char('country_code', 2)->nullable()->after('region');
            $table->string('country_name')->nullable()->after('country_code');
            $table->decimal('latitude', 8, 6)->nullable()->after('country_name');
            $table->decimal('longitude', 9, 6)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('circles', function (Blueprint $table) {
            $table->dropColumn(['geonames_id', 'place_name', 'region', 'country_code', 'country_name', 'latitude', 'longitude']);
        });
    }
};
