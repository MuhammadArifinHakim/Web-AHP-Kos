<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('ahp_calculations', function (Blueprint $table) {
            $table->json('alternative_weights')->nullable()->after('boarding_house_scores');
            $table->json('alternatives_consistency')->nullable()->after('alternative_weights');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('ahp_calculations', function (Blueprint $table) {
            $table->dropColumn(['alternative_weights', 'alternatives_consistency']);
        });
    }
};
