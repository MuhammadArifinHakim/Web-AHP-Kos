<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('questionnaire_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');
            $table->json('pairwise_values');
            $table->decimal('consistency_ratio', 5, 4)->nullable();
            $table->string('source')->default('manual');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('questionnaire_responses');
    }
};