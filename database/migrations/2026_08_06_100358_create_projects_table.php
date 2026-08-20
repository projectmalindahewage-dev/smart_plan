<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('schedule_date');
            $table->string('daily_goal')->nullable();
            $table->string('daily_theme')->nullable();
            $table->string('mood', 64)->nullable();
            $table->string('energy', 64)->nullable();
            $table->text('notes')->nullable();
            $table->text('reflection')->nullable();
            $table->decimal('planned_hours', 5, 2)->nullable();
            $table->decimal('actual_hours', 5, 2)->nullable();
            $table->unsignedTinyInteger('productivity_score')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};