<?php

use App\Http\Controllers\Communications\ChannelController;
use App\Http\Controllers\Communications\ChannelMemberController;
use App\Http\Controllers\Communications\MentionController;
use App\Http\Controllers\Communications\MessageAttachmentController;
use App\Http\Controllers\Communications\MessageController;
use App\Http\Controllers\Communications\MessageReactionController;
use App\Http\Controllers\Communications\MessageThemeController;
use App\Http\Controllers\Communications\TeamMemberAuthController;
use App\Http\Controllers\Communications\TeamMemberController;
use App\Http\Controllers\Communications\TypingController;
use Illuminate\Support\Facades\Route;

// --- Login de miembros de equipo (sesión propia, guard "team_member") ---
Route::prefix('equipo')->name('team-member.')->group(function () {
    Route::get('login', [TeamMemberAuthController::class, 'create'])->name('login');
    Route::post('login', [TeamMemberAuthController::class, 'store'])->name('login.store');

    Route::middleware('auth:team_member')->group(function () {
        Route::post('logout', [TeamMemberAuthController::class, 'destroy'])->name('logout');
    });
});

// --- Comunicación: accesible por el contador y por sus miembros de equipo ---
Route::middleware('auth:web,team_member')->prefix('comunicacion')->name('communications.')->group(function () {

    Route::get('/', [ChannelController::class, 'index'])->name('index');
    Route::post('/', [ChannelController::class, 'store'])->name('store');
    Route::get('canales/{channel}', [ChannelController::class, 'show'])->name('show');

    Route::get ('canales/{channel}/mensajes', [MessageController::class, 'index'])->name('messages.index');
    Route::post('canales/{channel}/mensajes', [MessageController::class, 'store'])->name('messages.store');

    Route::post  ('canales/{channel}/miembros',            [ChannelMemberController::class, 'store'])  ->name('members.store');
    Route::delete('canales/{channel}/miembros/{channelMember}', [ChannelMemberController::class, 'destroy'])->name('members.destroy');

    Route::post  ('canales/{channel}/temas', [MessageThemeController::class, 'store'])->name('themes.store');

    Route::post('canales/{channel}/mensajes/{message}/reacciones', [MessageReactionController::class, 'store'])->name('messages.reactions.store');
    Route::post('canales/{channel}/escribiendo', [TypingController::class, 'heartbeat'])->name('typing.heartbeat');

    Route::get('adjuntos/{attachment}', [MessageAttachmentController::class, 'show'])->name('attachments.show');

    Route::get   ('menciones',              [MentionController::class, 'index'])   ->name('mentions.index');
    Route::patch ('menciones/{mention}/leida', [MentionController::class, 'markRead'])->name('mentions.read');

    // --- Gestión del equipo y apertura de canales contextuales (solo el contador dueño, guard "web") ---
    Route::middleware('auth:web')->group(function () {
        Route::get   ('equipo',              [TeamMemberController::class, 'index'])  ->name('team.index');
        Route::post  ('equipo',              [TeamMemberController::class, 'store'])  ->name('team.store');
        Route::patch ('equipo/{teamMember}', [TeamMemberController::class, 'update']) ->name('team.update');
        Route::delete('equipo/{teamMember}', [TeamMemberController::class, 'destroy'])->name('team.destroy');

        Route::get('contexto/{type}/{id}', [ChannelController::class, 'openContext'])->name('context.open');
    });
});
