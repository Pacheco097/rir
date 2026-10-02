<?php

namespace Tests\Feature;

use App\Models\Gap;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HomologationTest extends TestCase
{
    use RefreshDatabase;

    private Sector $sector;

    private User $leader;

    private User $ti;

    private Gap $gap;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sector = Sector::factory()->create();
        $this->leader = User::factory()->leaderOf($this->sector)->create();
        $this->ti = User::factory()->ti()->create();
        $this->gap = Gap::factory()->create([
            'sector_id' => $this->sector->id,
            'status' => 'em_execucao',
            'it_owner_id' => $this->ti->id,
            'sector_owner_id' => $this->leader->id,
        ]);
    }

    private function send(): void
    {
        Sanctum::actingAs($this->ti);
        $this->postJson("/api/gaps/{$this->gap->id}/homologation/send", [
            'solution' => 'Ajustado parâmetro MV_XYZ',
            'solution_evidence' => 'Print do teste',
        ])->assertOk()->assertJsonPath('status.value', 'homologacao');
    }

    public function test_sending_requires_solution_and_evidence(): void
    {
        Sanctum::actingAs($this->ti);

        $this->postJson("/api/gaps/{$this->gap->id}/homologation/send", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['solution', 'solution_evidence']);
    }

    public function test_leader_approval_concludes_the_gap(): void
    {
        $this->send();

        Sanctum::actingAs($this->leader);
        $this->postJson("/api/gaps/{$this->gap->id}/homologation/decide", [
            'approved' => true,
            'test_result' => 'Testado com 5 notas, ok.',
        ])->assertOk()->assertJsonPath('status.value', 'concluido');

        $gap = $this->gap->fresh();
        $this->assertNotNull($gap->completed_at);
        $this->assertSame($this->ti->id, $gap->completed_by);
        $this->assertDatabaseHas('homologations', ['gap_id' => $gap->id, 'result' => 'aprovado', 'tested_by' => $this->leader->id]);
    }

    public function test_leader_rejection_reopens_the_gap(): void
    {
        $this->send();

        Sanctum::actingAs($this->leader);
        $this->postJson("/api/gaps/{$this->gap->id}/homologation/decide", [
            'approved' => false,
            'test_result' => 'O problema continua.',
        ])->assertOk()->assertJsonPath('status.value', 'reaberto');
    }

    public function test_leader_from_another_sector_cannot_decide(): void
    {
        $this->send();

        $otherLeader = User::factory()->leaderOf(Sector::factory()->create())->create();
        Sanctum::actingAs($otherLeader);

        $this->postJson("/api/gaps/{$this->gap->id}/homologation/decide", [
            'approved' => true, 'test_result' => 'ok',
        ])->assertForbidden();
    }

    public function test_ti_cannot_approve_its_own_solution(): void
    {
        $this->send();

        $this->postJson("/api/gaps/{$this->gap->id}/homologation/decide", [
            'approved' => true, 'test_result' => 'ok',
        ])->assertForbidden();
    }
}
