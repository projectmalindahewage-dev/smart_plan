<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('priority', 32)->default('medium');
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status', 32)->default('pending');
            $table->boolean('enabled')->default(true);
            $table->unsignedTinyInteger('completion_percentage')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            // $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            // $table->string('importance', 32)->nullable();
            // $table->string('urgency', 32)->nullable();
            // $table->string('difficulty', 32)->nullable();
            
            // $table->unsignedInteger('estimated_minutes')->nullable();
            // $table->unsignedInteger('actual_minutes')->nullable();
            // $table->date('due_date')->nullable();
            // $table->dateTime('deadline')->nullable();
            
            // $table->string('required_energy', 32)->nullable();
            // $table->string('required_focus', 32)->nullable();
            // $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            // $table->string('repeat_rule')->nullable();
            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};