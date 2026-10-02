<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('sector_id')->constrained()->restrictOnDelete();
            $table->foreignId('leader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('it_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date')->index();
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->text('objective')->nullable();
            $table->string('status', 20)->default('agendada')->index();
            $table->text('notes')->nullable();
            // Pauta estruturada: objeto JSON com os 8 tópicos padrão.
            $table->json('agenda')->nullable();
            // Pós-reunião
            $table->longText('minutes')->nullable();   // Ata
            $table->text('summary')->nullable();
            $table->text('decisions')->nullable();
            $table->text('next_steps')->nullable();
            $table->foreignId('previous_meeting_id')->nullable()->constrained('meetings')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('meeting_participants', function (Blueprint $table) {
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['meeting_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_participants');
        Schema::dropIfExists('meetings');
    }
};
