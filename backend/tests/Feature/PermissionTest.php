<?php

namespace Tests\Feature;

use App\Models\Gap;
use App\Models\Meeting;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_consulta_can_read_but_not_create_gaps(): void
    {
        Sanctum::actingAs(User::factory()->create()); // perfil padrão: consulta
        $sector = Sector::factory()->create();

        $this->getJson('/api/gaps')->assertOk();
        $this->postJson('/api/gaps', ['title' => 'x', 'sector_id' => $sector->id])->assertForbidden();
    }

    public function test_leader_only_sees_own_sector(): void
    {
        $mine = Sector::factory()->create();
        $other = Sector::factory()->create();
        $this->actingAsLeader($mine);

        $own = Gap::factory()->create(['sector_id' => $mine->id]);
        $foreign = Gap::factory()->create(['sector_id' => $other->id]);

        $this->getJson('/api/gaps')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $own->id);
        $this->getJson("/api/gaps/{$foreign->id}")->assertForbidden();

        Meeting::factory()->create(['sector_id' => $other->id]);
        $this->getJson('/api/meetings')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_leader_registers_gap_always_in_own_sector(): void
    {
        $mine = Sector::factory()->create();
        $other = Sector::factory()->create();
        $leader = $this->actingAsLeader($mine);

        $this->postJson('/api/gaps', [
            'title' => 'Relatório', 'description' => 'desc', 'sector_id' => $other->id,
            'impact' => 'baixo', 'urgency' => 'sem_urgencia',
        ])->assertCreated()
            ->assertJsonPath('sector_id', $mine->id)
            ->assertJsonPath('sector_owner_id', $leader->id);
    }

    public function test_leader_can_only_complement_not_reprioritize(): void
    {
        $sector = Sector::factory()->create();
        $this->actingAsLeader($sector);
        $gap = Gap::factory()->create(['sector_id' => $sector->id]);

        $this->patchJson("/api/gaps/{$gap->id}", ['description' => 'Mais detalhes', 'priority' => 'critica'])
            ->assertOk()
            ->assertJsonPath('description', 'Mais detalhes')
            ->assertJsonPath('priority.value', 'media'); // prioridade ignorada
    }

    public function test_only_admin_manages_users_and_catalogs(): void
    {
        $this->actingAsTi();
        $this->postJson('/api/users', [])->assertForbidden();
        $this->postJson('/api/categories', ['name' => 'Nova'])->assertForbidden();

        $this->actingAsAdmin();
        $this->postJson('/api/categories', ['name' => 'Nova'])->assertCreated();
    }

    public function test_only_admin_deletes_gaps(): void
    {
        $gap = Gap::factory()->create();

        $this->actingAsTi();
        $this->deleteJson("/api/gaps/{$gap->id}")->assertForbidden();

        $this->actingAsAdmin();
        $this->deleteJson("/api/gaps/{$gap->id}")->assertNoContent();
        $this->assertSoftDeleted($gap);
    }
}
