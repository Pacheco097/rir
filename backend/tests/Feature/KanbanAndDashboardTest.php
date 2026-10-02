<?php

namespace Tests\Feature;

use App\Models\Gap;
use App\Models\Meeting;
use App\Models\Sprint;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KanbanAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_kanban_groups_gaps_into_columns(): void
    {
        $this->actingAsTi();
        Gap::factory()->create(['status' => 'backlog']);
        Gap::factory()->create(['status' => 'em_execucao', 'it_owner_id' => User::factory()->ti()->create()->id]);

        $response = $this->getJson('/api/kanban')->assertOk()->assertJsonCount(6);

        $this->assertCount(1, $response->json('0.gaps'));  // Backlog
        $this->assertCount(1, $response->json('2.gaps'));  // Em andamento
    }

    public function test_dragging_card_changes_status(): void
    {
        $this->actingAsTi();
        $gap = Gap::factory()->create(['status' => 'planejado', 'it_owner_id' => User::factory()->ti()->create()->id]);

        $this->postJson("/api/kanban/{$gap->id}/move", ['column' => 'em_andamento'])
            ->assertOk()->assertJsonPath('status.value', 'em_execucao');
    }

    public function test_cannot_drop_card_on_done_column(): void
    {
        $this->actingAsTi();
        $gap = Gap::factory()->create(['status' => 'em_execucao', 'it_owner_id' => User::factory()->ti()->create()->id]);

        $this->postJson("/api/kanban/{$gap->id}/move", ['column' => 'concluido'])->assertUnprocessable();
    }

    public function test_assigning_gap_to_sprint_is_audited(): void
    {
        $this->actingAsTi();
        $sprint = Sprint::factory()->create(['number' => 7, 'name' => 'Fiscal']);
        $gap = Gap::factory()->create();

        $this->postJson("/api/sprints/{$sprint->id}/gaps", ['gap_ids' => [$gap->id]])->assertOk();

        $this->assertDatabaseHas('gap_histories', ['gap_id' => $gap->id, 'field' => 'sprint_id', 'new_value' => 'Sprint 7 - Fiscal']);
    }

    public function test_meeting_can_generate_many_gaps_and_schedule_next(): void // Regra 7
    {
        $this->actingAsTi();
        $meeting = Meeting::factory()->create();
        $gaps = Gap::factory()->count(3)->create(['sector_id' => $meeting->sector_id]);

        $this->patchJson("/api/meetings/{$meeting->id}", ['gap_ids' => $gaps->pluck('id')->all(), 'minutes' => 'Ata'])
            ->assertOk()->assertJsonCount(3, 'gaps');

        $this->postJson("/api/meetings/{$meeting->id}/next", ['date' => now()->addWeek()->toDateString(), 'start_time' => '14:00'])
            ->assertCreated()
            ->assertJsonPath('previous_meeting_id', $meeting->id)
            ->assertJsonPath('sector_id', $meeting->sector_id);
    }

    public function test_dashboard_with_seeded_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAsAdmin();

        $response = $this->getJson('/api/dashboard')->assertOk()
            ->assertJsonStructure([
                'totals' => ['total', 'open', 'critical', 'in_progress', 'blocked', 'homologation', 'completed', 'overdue', 'avg_resolution_days'],
                'by_sector', 'by_system', 'by_category', 'by_priority', 'by_status', 'by_owner',
                'evolution', 'completed_by_sprint', 'overdue', 'priority_gaps', 'upcoming_meetings',
            ]);

        $this->assertSame(Gap::count(), $response->json('totals.total'));
        $this->assertCount(6, $response->json('evolution'));
    }
}
