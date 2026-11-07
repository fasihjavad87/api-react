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
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            // کلید خارجی به جدول users با حذف آبشاری
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // شناسه یکتای سخت‌افزاری/کوکی مرورگر
            $table->uuid('device_id');

            // اطلاعات دستگاه و مرورگر
            $table->string('device_name')->nullable(); // مثلا: Chrome on Windows
            $table->string('platform')->nullable();    // Windows, Linux, macOS, Android, iOS
            $table->string('browser')->nullable();     // Chrome, Firefox, Safari, Edge
           $table->string('device_type', 30)->default('desktop');

            $table->timestamps();

            // ایندکس ترکیبی یکتا: یک کاربر نمی‌تواند دو سطر با device_id یکسان داشته باشد
            $table->unique(['user_id', 'device_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
