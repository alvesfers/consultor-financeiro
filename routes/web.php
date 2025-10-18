<?php

use App\Http\Controllers\Admin\ConsultantController as AdminConsultantController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\Client\CardInvoiceController;     // dashboard do cliente (no contexto do consultor)
use App\Http\Controllers\Client\InvestmentController;       // NOVO: movimentações de investimento
use App\Http\Controllers\ClientAccountController;           // pagar fatura do cartão
use App\Http\Controllers\ClientDashboardController;
use App\Http\Controllers\ClientGoalController;
use App\Http\Controllers\ClientInvoiceController;
use App\Http\Controllers\ClientTransactionController;
use App\Http\Controllers\ClientTransactionsController;

use App\Http\Controllers\Consultant\CategoryController as ConsultantCategoryController;
use App\Http\Controllers\Consultant\ClientController as ConsultantClientController;
use App\Http\Controllers\Consultant\TaskController as ConsultantTaskController;
use App\Http\Controllers\ConsultantDashboardController;     // seu dashboard no namespace raiz
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

        'consultant' => $user->consultant
            ? redirect()->route('consultant.dashboard', ['consultant' => $user->consultant->id])
            : abort(403, 'Perfil de consultor não encontrado.'),

        // cliente navega no contexto do consultor: /{consultant}/client/dashboard
        'client' => $user->client
            ? redirect()->route('client.dashboard', ['consultant' => $user->client->consultant_id])
            : abort(403, 'Perfil de cliente não encontrado.'),

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
 * SETTINGS (todas as roles autenticadas)
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

        Route::resource('consultants', AdminConsultantController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])
            ->names('consultants');
    });

/**
 * CONSULTANT (multi-tenant): /{consultant}/...
 * -> cria, entre outras, a rota consultant.tasks.index
 */
Route::prefix('{consultant}')
    ->whereNumber('consultant')
    ->name('consultant.')   // <-- mantenha NO SINGULAR
    ->middleware(['auth', 'verified', 'role:consultant'])
    ->group(function () {
        // Dashboard (precisa existir para o layout)
        Route::get('/dashboard', [ConsultantDashboardController::class, 'index'])
            ->name('dashboard');

        // Outros recursos
        Route::resource('clients', ConsultantClientController::class)->names('clients');
        Route::resource('tasks', ConsultantTaskController::class)->names('tasks');

        // Categorias
        Route::patch('categories/{category}/toggle', [ConsultantCategoryController::class, 'toggle'])
            ->name('categories.toggle');
        Route::resource('categories', ConsultantCategoryController::class)->names('categories');

        // Subcategorias (na MESMA controller)
        Route::post('categories/{category}/subcategories', [ConsultantCategoryController::class, 'subStore'])
            ->name('categories.subcategories.store');
        Route::put('categories/{category}/subcategories/{subcategory}', [ConsultantCategoryController::class, 'subUpdate'])
            ->name('categories.subcategories.update');
        Route::delete('categories/{category}/subcategories/{subcategory}', [ConsultantCategoryController::class, 'subDestroy'])
            ->name('categories.subcategories.destroy');
    });


/**
 * CLIENT (no contexto do consultor): /{consultant}/client/...
 */
Route::prefix('{consultant}/client')
    ->whereNumber('consultant')
    ->name('client.')
    ->middleware(['auth', 'verified', 'role:client'])
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', [ClientDashboardController::class, 'index'])
            ->name('dashboard');

        // Transações (criar)
        Route::post('/transactions', [ClientTransactionController::class, 'store'])
            ->name('transactions.store');

        // Movimentações de investimento
        Route::post('/investments/move', [InvestmentController::class, 'move'])
            ->name('investments.move');

        // Contas / Cartões
        Route::get('accounts', [ClientAccountController::class, 'index'])->name('accounts.index');
        Route::post('accounts', [ClientAccountController::class, 'storeAccount'])->name('accounts.store');
        Route::post('cards', [ClientAccountController::class, 'storeCard'])->name('cards.store');
        Route::patch('cards/{card}', [ClientAccountController::class, 'updateCard'])->name('cards.update');

        // Faturas (derivadas de transactions)
        Route::get('invoices', [ClientInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/{invoice}', [ClientInvoiceController::class, 'show'])->name('invoices.show');
        Route::post('invoices/{invoice}/mark-paid', [ClientInvoiceController::class, 'markPaid'])->name('invoices.markPaid');
        Route::post('invoices/{invoice}/pay', [ClientInvoiceController::class, 'pay'])->name('invoices.pay');

        Route::get('goals', [ClientGoalController::class, 'index'])->name('goals.index');

        Route::post('/cards/{card}/invoices/{invoiceMonth}/pay', [CardInvoiceController::class, 'pay'])
            ->whereNumber('card')
            ->where(['invoiceMonth' => '[0-9]{4}-[0-9]{2}']) // YYYY-MM
            ->name('cards.invoices.pay');

        // Listar transações (se existir essa página)
        Route::get('transactions', [ClientTransactionsController::class, 'index'])->name('transactions.index');
        Route::get('transactions/export', [ClientTransactionsController::class, 'index'])->name('transactions.export');
    });

require __DIR__.'/auth.php';
