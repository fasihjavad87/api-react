<?php

use App\Enums\EpisodeStatus;
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
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained('movies')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->unsignedSmallInteger('episode_number');
            $table->string('status')->default(EpisodeStatus::DRAFT->value);
            $table->dateTime('publish_at')->nullable();
            $table->unsignedSmallInteger('runtime')->nullable();
            $table->boolean('is_free')->default(false);
            $table->timestamps();

            $table->unique(['movie_id', 'episode_number']);
            $table->index(['movie_id', 'status', 'publish_at'], 'episodes_next_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
