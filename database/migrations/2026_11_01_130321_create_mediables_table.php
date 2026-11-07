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
        Schema::create('mediables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->morphs('mediable');
            $table->unsignedTinyInteger('index')->default(0);
            $table->unsignedInteger('order')->default(0);
            
            $table->unique(['media_id','mediable_type', 'mediable_id'], 'mediables_unique');
            $table->index(['mediable_type', 'mediable_id', 'index'], 'mediables_primary_idx');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mediables');
    }
};
