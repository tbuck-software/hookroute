<?php

use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DestinationController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SourceController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\WebhookReceiverController;
use App\Services\RegistrationGate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn (Request $request, RegistrationGate $registration) => Inertia::render('Welcome', [
    'canRegister' => $registration->allows($request),
]))->name('home');

Route::match(['POST', 'PUT', 'PATCH'], '/hooks/{source:public_id}/{secret}', WebhookReceiverController::class)
    ->middleware('throttle:webhooks')
    ->name('hooks.receive');

Route::get('/invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'redirect'])->name('dashboard');
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept'])->name('invitations.accept');

    Route::prefix('/projects/{project}')->name('projects.')->group(function () {
        Route::get('/', [DashboardController::class, 'show'])->name('dashboard');
        Route::patch('/', [ProjectController::class, 'update'])->name('update');
        Route::delete('/', [ProjectController::class, 'destroy'])->name('destroy');

        Route::get('/sources', [SourceController::class, 'index'])->name('sources.index');
        Route::post('/sources', [SourceController::class, 'store'])->name('sources.store');
        Route::patch('/sources/{source}', [SourceController::class, 'update'])->name('sources.update');
        Route::post('/sources/{source}/rotate', [SourceController::class, 'rotate'])->name('sources.rotate');
        Route::delete('/sources/{source}', [SourceController::class, 'destroy'])->name('sources.destroy');

        Route::get('/destinations', [DestinationController::class, 'index'])->name('destinations.index');
        Route::post('/destinations', [DestinationController::class, 'store'])->name('destinations.store');
        Route::patch('/destinations/{destination}', [DestinationController::class, 'update'])->name('destinations.update');
        Route::delete('/destinations/{destination}', [DestinationController::class, 'destroy'])->name('destinations.destroy');

        Route::get('/routes', [ConnectionController::class, 'index'])->name('connections.index');
        Route::post('/routes', [ConnectionController::class, 'store'])->name('connections.store');
        Route::patch('/routes/{connection}', [ConnectionController::class, 'update'])->name('connections.update');
        Route::delete('/routes/{connection}', [ConnectionController::class, 'destroy'])->name('connections.destroy');

        Route::get('/events', [EventController::class, 'index'])->name('events.index');
        Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
        Route::post('/events/{event}/deliveries/{delivery}/replay', [EventController::class, 'replay'])->name('deliveries.replay');

        Route::get('/team', [TeamController::class, 'index'])->name('team.index');
        Route::post('/team/invitations', [TeamController::class, 'invite'])->name('team.invite');
        Route::patch('/team/{user}', [TeamController::class, 'updateRole'])->name('team.update');
        Route::delete('/team/{user}', [TeamController::class, 'remove'])->name('team.remove');
        Route::post('/team/{user}/transfer-ownership', [TeamController::class, 'transferOwnership'])->name('team.transfer-owner');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
