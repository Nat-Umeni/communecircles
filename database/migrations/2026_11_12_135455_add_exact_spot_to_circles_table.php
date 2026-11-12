<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A circle's own exact spot (its land), beside the GeoNames town it's near. The town's coordinates move to
 * place_latitude/place_longitude, next to place_name, and latitude/longitude become the circle's own.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('circles', function (Blueprint $table) {
            $table->renameColumn('latitude', 'place_latitude');
            $table->renameColumn('longitude', 'place_longitude');
        });

        Schema::table('circles', function (Blueprint $table) {
            $table->decimal('latitude', 8, 6)->nullable()->after('place_longitude');
            $table->decimal('longitude', 9, 6)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('circles', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });

        Schema::table('circles', function (Blueprint $table) {
            $table->renameColumn('place_latitude', 'latitude');
            $table->renameColumn('place_longitude', 'longitude');
        });
    }
};
