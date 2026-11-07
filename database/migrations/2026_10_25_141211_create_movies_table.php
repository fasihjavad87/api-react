<?php

use App\Enums\ContentType;
use App\Enums\MovieStatus;
use App\Enums\PlaybackStatus;
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
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('e_name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('content_type')->default(ContentType::MOVIE->value);
            $table->string('status')->default(MovieStatus::DRAFT->value);
            $table->string('playback_status')->default(PlaybackStatus::UPCOMING->value);
            $table->boolean('is_free')->default(false);
            $table->unsignedSmallInteger('total_episodes')->default(0);
            $table->unsignedSmallInteger('runtime')->nullable();
            $table->string('schedule_pattern')->nullable();
            $table->json('air_days')->nullable();
            $table->time('air_time')->nullable();
            $table->date('first_air_date')->nullable();
            $table->timestamps();

            $table->index(['content_type', 'status']);
            $table->index(['content_type', 'playback_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
