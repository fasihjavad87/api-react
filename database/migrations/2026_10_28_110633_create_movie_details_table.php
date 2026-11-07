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
        Schema::create('movie_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->unique()->constrained('movies')->cascadeOnDelete();
            $table->unsignedSmallInteger('release_year')->nullable();
            $table->string('age_rating')->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->json('seasons')->nullable();
            $table->json('air_schedule_display')->nullable();

            $table->index('release_year');
            $table->index('age_rating');
            $table->index('rating');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movie_details');
    }
};
