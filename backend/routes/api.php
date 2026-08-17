<?php

use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IntegrationController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\StewardshipReportController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Públicas
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login']);

// Invitaciones (públicas: verificar y aceptar)
Route::get('/invitations/{token}', [InvitationController::class, 'show']);
Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept']);

/*
|--------------------------------------------------------------------------
| Rutas Protegidas (Sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Invitaciones (crear — solo Presidencia/Admin)
    Route::post('/invitations', [InvitationController::class, 'store']);

    // ---- Admin ----
    Route::prefix('admin')->middleware('admin')->group(function () {
        // Usuarios
        Route::get('/users', [UserController::class, 'index']);
        Route::patch('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);

        // Integraciones
        Route::get('/integrations', [IntegrationController::class, 'index']);
        Route::post('/integrations', [IntegrationController::class, 'upsert']);
        Route::post('/integrations/{integration}/validate', [IntegrationController::class, 'validate']);
        Route::post('/integrations/{integration}/toggle', [IntegrationController::class, 'toggle']);
        Route::delete('/integrations/{integration}', [IntegrationController::class, 'destroy']);
    });

    // ---- Fase 3: Asignaciones y Reportes ----
    Route::apiResource('assignments', AssignmentController::class);
    Route::apiResource('reports', StewardshipReportController::class)->only(['index', 'store', 'show']);

    // ---- Fase 4: Llamamientos ----
    // Route::apiResource('callings', CallingController::class);
    // Route::post('callings/{calling}/vote', [CallingController::class, 'vote']);

    // ---- Fase 5: Reuniones ----
    // Route::apiResource('meetings', MeetingController::class);
    // Route::post('meetings/{meeting}/invite', [MeetingController::class, 'invite']);
    // Route::patch('meetings/{meeting}/rsvp', [MeetingController::class, 'rsvp']);
    // Route::post('meetings/{meeting}/upload-audio', [MeetingController::class, 'uploadAudio']);

    // ---- IA ----
    Route::post('ai/improve-text', [AiController::class, 'improveText']);
});
