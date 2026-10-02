<?php

use Illuminate\Support\Facades\Route;

// Backend é somente API; a interface fica no projeto /frontend (React).
Route::get('/', fn () => response()->json([
    'app' => config('app.name'),
    'api' => url('/api'),
    'health' => url('/up'),
]));
