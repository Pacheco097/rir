<?php

namespace App\Providers;

use App\Models\Gap;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Em desenvolvimento, acusa N+1 (lazy loading) e atributos não preenchíveis.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Grava "gap"/"meeting" na coluna attachable_type em vez do nome da classe PHP.
        Relation::enforceMorphMap([
            'gap' => Gap::class,
            'meeting' => Meeting::class,
            'user' => User::class, // tokens do Sanctum (personal_access_tokens.tokenable_type)
        ]);

        JsonResource::withoutWrapping();
    }
}
