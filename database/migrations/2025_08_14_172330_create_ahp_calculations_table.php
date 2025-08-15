<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ahp_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable('users')->constrained()->onDelete('cascade');
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');
            $table->json('criteria_weights');
            $table->json('boarding_house_scores');
            $table->json('ranking');
            $table->decimal('consistency_ratio', 5, 4);
            $table->string('weight_method');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ahp_calculations');
    }
};