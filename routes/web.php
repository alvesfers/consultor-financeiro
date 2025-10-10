<?php

use App\Http\Controllers\Admin\ConsultantController as AdminConsultantController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\ClientDashboardController;
use App\Http\Controllers\Consultant\ClientController as ConsultantClientController;
use App\Http\Controllers\Consultant\TaskController as ConsultantTaskController;
use App\Http\Controllers\ConsultantDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');

/**
 * /dashboard → redireciona conforme o tipo de usuário logado
 */
Route::get('/dashboard', function () {
    $user = auth()->user();

    if (! $user) {
        return redirect()->route('login');
    }

return match ($user->role) {
    'admin' => redirect()->route('admin.dashboard'),
    'consultant' => redirect()->route('consultants.dashboard', ['consultant' => $user->consultant?->id]),
    'client' => redirect()->route('client.dashboard'),
    default => abort(403),
};

})->middleware(['auth'])->name('dashboard');

/**
 * PERFIL (todas as roles)
 */
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/**
 * SETTINGS (visível a todos)
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
});

/**
 * ADMIN
 */
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'verified', 'role:admin'])
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::resource('/consultants', AdminConsultantController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])
            ->names('consultants');
    });

/**
 * CONSULTANT (multi-tenant)
 *
 * Exemplo: /1/dashboard ou /1/clients/5
 */
Route::prefix('{consultant}')
    ->name('consultant.')         // <-- SINGULAR
    ->group(function () {
        Route::get('/dashboard', [ConsultantDashboardController::class, 'index'])
            ->name('dashboard');  // -> consultants.dashboard
        Route::resource('/clients', ConsultantClientController::class)
            ->names('clients');   // -> consultants.clients.index, ...
    });



/**
 * CLIENT
 */
Route::prefix('client')
    ->name('client.')
    ->middleware(['auth', 'verified', 'role:client'])
    ->group(function () {
        Route::get('/dashboard', [ClientDashboardController::class, 'index'])->name('dashboard');
        // futuras rotas de contas/transações aqui
    });

require __DIR__.'/auth.php';
