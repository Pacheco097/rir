<?php

namespace Tests;

use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    protected function actingAsAdmin(): User
    {
        $user = User::factory()->admin()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    protected function actingAsTi(): User
    {
        $user = User::factory()->ti()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    protected function actingAsLeader(Sector $sector): User
    {
        $user = User::factory()->leaderOf($sector)->create();
        $sector->update(['leader_id' => $user->id]);
        Sanctum::actingAs($user);

        return $user;
    }
}
