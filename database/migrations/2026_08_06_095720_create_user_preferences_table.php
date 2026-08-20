<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->time('wake_time')->nullable();
            $table->time('sleep_time')->nullable();
            $table->time('working_start')->nullable();
            $table->time('working_end')->nullable();
            $table->string('productivity_pattern')->nullable();
            $table->unsignedInteger('default_task_duration')->default(60);
            $table->string('week_start', 16)->default('monday');
            $table->boolean('notification_enabled')->default(true);
            $table->string('theme', 32)->default('system');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};