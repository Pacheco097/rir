<?php

namespace Tests\Feature;

use App\Models\Gap;
use App\Models\Sector;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GapTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Sector $sector, array $overrides = []): array
    {
        return [
            'title' => 'Duplicidade de produtos',
            'description' => 'Produtos cadastrados em duplicidade no Protheus.',
            'sector_id' => $sector->id,
            'impact' => 'medio',
            'urgency' => 'curto_prazo',
            ...$overrides,
        ];
    }

    public function test_creates_gap_with_sequential_code_and_suggested_priority(): void
    {
        $this->actingAsTi();
        $sector = Sector::factory()->create(['leader_id' => User::factory()->create()->id]);
        $system = System::factory()->create();

        $first = $this->postJson('/api/gaps', $this->payload($sector, ['system_ids' => [$system->id]]))
            ->assertCreated()
            ->assertJsonPath('code', 'GAP-0001')
            ->assertJsonPath('status.value', 'levantado')
            ->assertJsonPath('priority.value', 'alta') // médio(2) + curto prazo(3) = 5 => alta
            ->assertJsonPath('sector_owner_id', $sector->leader_id) // Regra 2: responsável padrão
            ->assertJsonPath('systems.0.id', $system->id);

        $this->postJson('/api/gaps', $this->payload($sector))->assertJsonPath('code', 'GAP-0002');

        $this->assertDatabaseHas('gap_histories', ['gap_id' => $first->json('id'), 'field' => 'criado']);
    }

    public function test_critical_gap_requires_justification(): void // Regra 3
    {
        $this->actingAsTi();
        $sector = Sector::factory()->create(['leader_id' => User::factory()->create()->id]);

        $this->postJson('/api/gaps', $this->payload($sector, ['impact' => 'critico']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('critical_justification');

        $this->postJson('/api/gaps', $this->payload($sector, ['impact' => 'critico', 'critical_justification' => 'Para o faturamento']))
            ->assertCreated()
            ->assertJsonPath('priority.value', 'critica');
    }

    public function test_priority_change_is_recorded_in_history(): void // Regra 4
    {
        $user = $this->actingAsTi();
        $gap = Gap::factory()->create();

        $this->patchJson("/api/gaps/{$gap->id}", ['priority' => 'alta'])->assertOk();

        $this->assertDatabaseHas('gap_histories', [
            'gap_id' => $gap->id,
            'user_id' => $user->id,
            'field' => 'priority',
            'old_value' => 'Média',
            'new_value' => 'Alta',
        ]);
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $this->actingAsTi();
        $gap = Gap::factory()->create();

        $this->postJson("/api/gaps/{$gap->id}/transition", ['status' => 'em_execucao'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Transição não permitida: Levantado → Em execução.');
    }

    public function test_it_owner_is_required_to_prioritize(): void // Regra 2
    {
        $this->actingAsTi();
        $gap = Gap::factory()->create(['status' => 'triagem']);

        $this->postJson("/api/gaps/{$gap->id}/transition", ['status' => 'priorizado'])->assertUnprocessable();

        $gap->update(['it_owner_id' => User::factory()->ti()->create()->id]);
        $this->postJson("/api/gaps/{$gap->id}/transition", ['status' => 'priorizado'])
            ->assertOk()
            ->assertJsonPath('status.value', 'priorizado');
    }

    public function test_blocking_requires_reason_and_creates_comment(): void
    {
        $this->actingAsTi();
        $gap = Gap::factory()->create(['status' => 'triagem']);

        $this->postJson("/api/gaps/{$gap->id}/transition", ['status' => 'bloqueado'])->assertUnprocessable();
        $this->postJson("/api/gaps/{$gap->id}/transition", ['status' => 'bloqueado', 'reason' => 'Aguardando TOTVS'])->assertOk();

        $this->assertDatabaseHas('gap_comments', ['gap_id' => $gap->id, 'body' => '[Status: Bloqueado] Aguardando TOTVS']);
    }

    public function test_cannot_conclude_without_homologation(): void // Regra 1
    {
        $this->actingAsAdmin();
        $gap = Gap::factory()->create(['status' => 'em_execucao', 'it_owner_id' => User::factory()->ti()->create()->id]);

        $this->postJson("/api/gaps/{$gap->id}/transition", ['status' => 'concluido'])->assertUnprocessable();
        $this->assertSame('em_execucao', $gap->fresh()->status);
    }

    public function test_gaps_can_be_linked_both_ways(): void // Regra 5
    {
        $this->actingAsTi();
        [$a, $b] = Gap::factory()->count(2)->create();

        $this->postJson("/api/gaps/{$a->id}/links", ['related_gap_id' => $b->id])->assertOk()
            ->assertJsonPath('related_gaps.0.id', $b->id);

        $this->assertTrue($b->relatedGaps()->whereKey($a->id)->exists());
    }

    public function test_gap_can_have_many_tasks(): void // Regra 6
    {
        $this->actingAsTi();
        $gap = Gap::factory()->create();

        $this->postJson("/api/gaps/{$gap->id}/tasks", ['title' => 'Analisar'])->assertCreated();
        $this->postJson("/api/gaps/{$gap->id}/tasks", ['title' => 'Desenvolver'])->assertCreated();

        $this->getJson("/api/gaps/{$gap->id}/tasks")->assertOk()->assertJsonCount(2);
    }

    public function test_incident_can_be_converted_to_gap(): void
    {
        $this->actingAsTi();
        $incident = Gap::factory()->incident()->create();

        $this->postJson("/api/gaps/{$incident->id}/convert", ['reason' => 'Requer desenvolvimento'])
            ->assertOk()
            ->assertJsonPath('type.value', 'gap')
            ->assertJsonPath('status.value', 'triagem')
            ->assertJsonPath('code', 'GAP-0001');

        $this->assertDatabaseHas('gap_histories', ['gap_id' => $incident->id, 'field' => 'type', 'new_value' => 'GAP/Melhoria']);
    }

    public function test_search_by_exact_code_returns_redirect(): void
    {
        $this->actingAsTi();
        $gap = Gap::factory()->create(['code' => 'GAP-0025']);

        $this->getJson('/api/search?q=gap-25')->assertOk()->assertJsonPath('redirect', "/gaps/{$gap->id}");
    }
}
