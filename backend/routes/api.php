<?php

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\GapCommentController;
use App\Http\Controllers\Api\GapController;
use App\Http\Controllers\Api\HomologationController;
use App\Http\Controllers\Api\KanbanController;
use App\Http\Controllers\Api\LookupController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SectorController;
use App\Http\Controllers\Api\SprintController;
use App\Http\Controllers\Api\SystemController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API REST — prefixo /api (definido em bootstrap/app.php)
|--------------------------------------------------------------------------
| Organização:
|  1. Rotas públicas (login), com limite de tentativas (throttle) contra força bruta.
|  2. Rotas autenticadas (auth:sanctum).
|  3. Dentro delas, o middleware "permission:" aplica o RBAC por perfil.
*/

Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::get('lookups', LookupController::class);
    Route::get('search', SearchController::class);

    // Dashboards
    Route::get('dashboard', [DashboardController::class, 'index'])->middleware('permission:dashboard.view|dashboard.sector');
    Route::get('dashboard/sectors/{sector}', [DashboardController::class, 'sector'])->middleware('permission:dashboard.view|dashboard.sector');

    // Cadastros base — leitura para todos os autenticados, escrita só admin.
    Route::apiResource('sectors', SectorController::class)->only(['index', 'show']);
    Route::apiResource('systems', SystemController::class)->only(['index', 'show']);
    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);

    Route::middleware('permission:admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::apiResource('sectors', SectorController::class)->except(['index', 'show']);
        Route::apiResource('systems', SystemController::class)->except(['index', 'show']);
        Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
    });

    // Reuniões
    Route::middleware('permission:meetings.view')->group(function () {
        Route::get('meetings', [MeetingController::class, 'index']);
        Route::get('meetings/{meeting}', [MeetingController::class, 'show']);
    });
    Route::middleware('permission:meetings.manage')->group(function () {
        Route::post('meetings', [MeetingController::class, 'store']);
        Route::match(['put', 'patch'], 'meetings/{meeting}', [MeetingController::class, 'update']);
        Route::delete('meetings/{meeting}', [MeetingController::class, 'destroy']);
        Route::post('meetings/{meeting}/next', [MeetingController::class, 'scheduleNext']);
        Route::post('meetings/{meeting}/attachments', [AttachmentController::class, 'storeForMeeting']);
    });

    // GAPs
    Route::middleware('permission:gaps.view')->group(function () {
        Route::get('gaps', [GapController::class, 'index']);
        Route::get('gaps/{gap}', [GapController::class, 'show']);
        Route::get('gaps/{gap}/history', [GapController::class, 'history']);
        Route::get('gaps/{gap}/comments', [GapCommentController::class, 'index']);
        Route::get('gaps/{gap}/tasks', [TaskController::class, 'index']);
        Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download']);
    });
    Route::post('gaps', [GapController::class, 'store'])->middleware('permission:gaps.create');
    Route::match(['put', 'patch'], 'gaps/{gap}', [GapController::class, 'update'])->middleware('permission:gaps.update|gaps.complement');
    Route::delete('gaps/{gap}', [GapController::class, 'destroy'])->middleware('permission:admin');
    Route::post('gaps/{gap}/transition', [GapController::class, 'transition'])->middleware('permission:gaps.transition|gaps.reopen');
    Route::post('gaps/{gap}/convert', [GapController::class, 'convert'])->middleware('permission:gaps.convert');
    Route::post('gaps/{gap}/links', [GapController::class, 'link'])->middleware('permission:gaps.update');
    Route::delete('gaps/{gap}/links/{related}', [GapController::class, 'unlink'])->middleware('permission:gaps.update');
    Route::post('gaps/{gap}/comments', [GapCommentController::class, 'store'])->middleware('permission:comments.create');
    Route::post('gaps/{gap}/attachments', [AttachmentController::class, 'storeForGap'])->middleware('permission:attachments.create');
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])->middleware('permission:attachments.create');

    // Homologação
    Route::post('gaps/{gap}/homologation/send', [HomologationController::class, 'send'])->middleware('permission:gaps.homologation.send');
    Route::post('gaps/{gap}/homologation/decide', [HomologationController::class, 'decide'])->middleware('permission:gaps.homologation.decide');

    // Tarefas (Regra 6)
    Route::middleware('permission:tasks.manage')->group(function () {
        Route::post('gaps/{gap}/tasks', [TaskController::class, 'store']);
        Route::match(['put', 'patch'], 'tasks/{task}', [TaskController::class, 'update']);
        Route::delete('tasks/{task}', [TaskController::class, 'destroy']);
    });

    // Kanban
    Route::get('kanban', [KanbanController::class, 'index'])->middleware('permission:gaps.view');
    Route::post('kanban/{gap}/move', [KanbanController::class, 'move'])->middleware('permission:gaps.transition');

    // Sprints (Scrum)
    Route::middleware('permission:sprints.view')->group(function () {
        Route::get('sprints', [SprintController::class, 'index']);
        Route::get('sprints/{sprint}', [SprintController::class, 'show']);
    });
    Route::middleware('permission:admin')->group(function () {
        Route::post('sprints', [SprintController::class, 'store']);
        Route::match(['put', 'patch'], 'sprints/{sprint}', [SprintController::class, 'update']);
        Route::delete('sprints/{sprint}', [SprintController::class, 'destroy']);
    });
    Route::middleware('permission:sprints.assign')->group(function () {
        Route::post('sprints/{sprint}/gaps', [SprintController::class, 'assignGaps']);
        Route::delete('sprints/{sprint}/gaps/{gap}', [SprintController::class, 'removeGap']);
    });
});
