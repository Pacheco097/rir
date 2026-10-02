<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gaps', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();          // GAP-0001 / INC-0001
            $table->string('type', 20)->default('gap')->index();
            $table->string('title');
            $table->text('description');
            $table->foreignId('sector_id')->constrained()->restrictOnDelete();
            $table->string('process')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('origin', 30)->nullable();
            $table->string('impact', 20)->index();
            $table->string('urgency', 20);
            $table->string('priority', 20)->index();
            $table->string('frequency', 20)->nullable();
            $table->text('critical_justification')->nullable(); // Regra 3
            $table->foreignId('sector_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('it_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('identified_at');
            $table->date('due_date')->nullable()->index();
            $table->foreignId('sprint_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30)->index();
            $table->text('notes')->nullable();
            // Solução / conclusão
            $table->text('solution')->nullable();
            $table->text('solution_evidence')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Índice composto para os filtros mais comuns do dashboard/kanban.
            $table->index(['sector_id', 'status']);
        });

        // Um GAP pode envolver vários sistemas.
        Schema::create('gap_system', function (Blueprint $table) {
            $table->foreignId('gap_id')->constrained()->cascadeOnDelete();
            $table->foreignId('system_id')->constrained()->cascadeOnDelete();
            $table->primary(['gap_id', 'system_id']);
        });

        // Regras 7 e 8: N reuniões <-> N gaps.
        Schema::create('gap_meeting', function (Blueprint $table) {
            $table->foreignId('gap_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->primary(['gap_id', 'meeting_id']);
        });

        // Regra 5: GAPs relacionados (auto-relacionamento).
        Schema::create('gap_links', function (Blueprint $table) {
            $table->foreignId('gap_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_gap_id')->constrained('gaps')->cascadeOnDelete();
            $table->primary(['gap_id', 'related_gap_id']);
        });

        Schema::create('gap_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gap_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        // Auditoria: uma linha por campo alterado.
        Schema::create('gap_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gap_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('field', 50);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        // Regra 6: um GAP gera várias tarefas.
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gap_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('a_fazer');
            $table->date('due_date')->nullable();
            $table->timestamps();
        });

        Schema::create('homologations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gap_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at');
            $table->foreignId('tested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tested_at')->nullable();
            $table->string('result', 20)->nullable(); // null = pendente
            $table->text('test_result')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Anexos polimórficos: servem para GAP e para Reunião.
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('homologations');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('gap_histories');
        Schema::dropIfExists('gap_comments');
        Schema::dropIfExists('gap_links');
        Schema::dropIfExists('gap_meeting');
        Schema::dropIfExists('gap_system');
        Schema::dropIfExists('gaps');
    }
};
