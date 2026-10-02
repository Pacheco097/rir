<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sprints', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number')->unique(); // "Sprint 12"
            $table->string('name');
            $table->text('goal')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('planejamento')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sprints');
    }
};
