<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\FirstAccessController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\MercadoController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

// ---------- Páginas legais (públicas) ----------
Route::get('/termos', [LegalController::class, 'terms'])->name('termos');
Route::get('/privacidade', [LegalController::class, 'privacy'])->name('privacidade');

// Página offline do PWA (pública; o service worker a exibe sem rede).
Route::view('/offline', 'pages.offline')->name('offline');

// ---------- Guests ----------
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.attempt');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('cadastro');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('cadastro.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.recuperar');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');

    Route::get('/first-access/{token}', [FirstAccessController::class, 'show'])->name('password.primeiro-acesso');
    Route::post('/first-access/{token}', [FirstAccessController::class, 'store'])->name('password.primeiro-acesso.store');
});

// ---------- Authenticated ----------
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware('family.role')->group(function () {
        Route::post('/notifications/read', [NotificationController::class, 'readAll'])->name('notificacoes.lidas');
        Route::get('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notificacoes.ler');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

        // User profile
        Route::get('/profile', [ProfileController::class, 'show'])->name('perfil');
        Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('perfil.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('perfil.update');
        Route::get('/profile/password', [ProfileController::class, 'editPassword'])->name('perfil.senha');
        Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('perfil.senha.update');
        Route::post('/profile/bot-code', [ProfileController::class, 'regenerateBotCode'])->name('perfil.botcode');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('perfil.destroy');

        // Expenses & Incomes
        Route::get('/expenses', [TransactionController::class, 'index'])->defaults('type', 'despesa')->name('despesas');
        Route::get('/incomes', [TransactionController::class, 'index'])->defaults('type', 'receita')->name('receitas');
        Route::get('/expenses/create', [TransactionController::class, 'create'])->defaults('type', 'despesa')->name('despesas.create');
        Route::get('/incomes/create', [TransactionController::class, 'create'])->defaults('type', 'receita')->name('receitas.create');
        Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('lancamentos.edit');
        Route::post('/expenses', [TransactionController::class, 'store'])->defaults('type', 'despesa')->name('despesas.store');
        Route::post('/incomes', [TransactionController::class, 'store'])->defaults('type', 'receita')->name('receitas.store');
        Route::patch('/transactions/{transaction}', [TransactionController::class, 'update'])->name('lancamentos.update');
        Route::post('/transactions/{transaction}/settle', [TransactionController::class, 'settle'])->name('lancamentos.settle');
        Route::get('/attachments/{attachment}', [TransactionController::class, 'downloadAttachment'])->name('anexos.download');
        Route::delete('/attachments/{attachment}', [TransactionController::class, 'destroyAttachment'])->name('anexos.destroy');

        // Accounts
        Route::get('/accounts', [AccountController::class, 'index'])->name('contas');
        Route::get('/accounts/transfer', [AccountController::class, 'createTransfer'])->name('contas.transfer.create');

        // Cards (invoice for everyone)
        Route::get('/cards', [CardController::class, 'index'])->name('cartoes');
        Route::get('/cards/items/create', [CardController::class, 'createItem'])->name('cartoes.itens.create');
        Route::post('/cards/items', [CardController::class, 'storeItem'])->name('cartoes.itens.store');
        Route::post('/cards/items/{item}/settle', [CardController::class, 'settleItem'])->name('cartoes.itens.settle');

        // Categories (read for everyone)
        Route::get('/categories', [CategoryController::class, 'index'])->name('categorias');

        // Family (read for everyone)
        Route::get('/family', [FamilyController::class, 'index'])->name('familia');

        // ----- Managers (admin / co_admin) -----
        Route::middleware('family.role:admin,co_admin')->group(function () {
            Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('lancamentos.destroy');

            Route::get('/accounts/create', [AccountController::class, 'create'])->name('contas.create');
            Route::post('/accounts', [AccountController::class, 'store'])->name('contas.store');
            Route::get('/accounts/{account}/edit', [AccountController::class, 'edit'])->name('contas.edit');
            Route::patch('/accounts/{account}', [AccountController::class, 'update'])->name('contas.update');
            Route::delete('/accounts/{account}', [AccountController::class, 'destroy'])->name('contas.destroy');
            Route::post('/accounts/transfer', [AccountController::class, 'transfer'])->name('contas.transfer');

            Route::get('/cards/create', [CardController::class, 'create'])->name('cartoes.create');
            Route::post('/cards', [CardController::class, 'store'])->name('cartoes.store');
            Route::get('/cards/{card}/edit', [CardController::class, 'edit'])->name('cartoes.edit');
            Route::patch('/cards/{card}', [CardController::class, 'update'])->name('cartoes.update');
            Route::delete('/cards/{card}', [CardController::class, 'destroy'])->name('cartoes.destroy');

            Route::get('/categories/create', [CategoryController::class, 'create'])->name('categorias.create');
            Route::post('/categories', [CategoryController::class, 'store'])->name('categorias.store');
            Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categorias.edit');
            Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categorias.update');
            Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categorias.destroy');

            Route::get('/investments', [InvestmentController::class, 'index'])->name('investimentos');
            Route::get('/investments/market', [InvestmentController::class, 'market'])->name('investimentos.mercado');

            Route::get('/market', [MercadoController::class, 'index'])->name('mercado');
            Route::get('/market/data', [MercadoController::class, 'dados'])->name('mercado.dados');
            Route::get('/investments/portfolios/create', [InvestmentController::class, 'createPortfolio'])->name('investimentos.carteiras.create');
            Route::post('/investments/portfolios', [InvestmentController::class, 'storePortfolio'])->name('investimentos.carteiras.store');
            Route::get('/investments/assets/create', [InvestmentController::class, 'createAsset'])->name('investimentos.ativos.create');
            Route::post('/investments/assets', [InvestmentController::class, 'storeAsset'])->name('investimentos.ativos.store');
            Route::get('/investments/assets/{asset}/edit', [InvestmentController::class, 'editAsset'])->name('investimentos.ativos.edit');
            Route::patch('/investments/assets/{asset}', [InvestmentController::class, 'updateAsset'])->name('investimentos.ativos.update');
            Route::get('/investments/contributions/create', [InvestmentController::class, 'createContribution'])->name('investimentos.aportes.create');
            Route::post('/investments/contributions', [InvestmentController::class, 'storeContribution'])->name('investimentos.aportes.store');
            Route::delete('/investments/assets/{asset}', [InvestmentController::class, 'destroyAsset'])->name('investimentos.ativos.destroy');

            Route::get('/family/invites/create', [FamilyController::class, 'createInvite'])->name('familia.convites.create');
            Route::post('/family/invites', [FamilyController::class, 'invite'])->name('familia.convites.store');
            Route::delete('/family/invites/{invite}', [FamilyController::class, 'revokeInvite'])->name('familia.convites.destroy');
            Route::delete('/family/members/{member}', [FamilyController::class, 'removeMember'])->name('familia.membros.destroy');
            Route::patch('/family/members/{member}/role', [FamilyController::class, 'updateRole'])->name('familia.membros.papel');

            Route::post('/cards/{card}/pay-invoice', [CardController::class, 'payInvoice'])->name('cartoes.fatura.pagar');

            Route::get('/settings', [SettingsController::class, 'index'])->name('configuracoes');
            Route::patch('/settings', [SettingsController::class, 'update'])->name('configuracoes.update');
            Route::patch('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('configuracoes.notificacoes');
            Route::post('/settings/sessions', [SettingsController::class, 'logoutOthers'])->name('configuracoes.sessoes');

            // Logs de auditoria (apenas admin).
            Route::get('/admin/logs', [AuditController::class, 'index'])->name('admin.logs');
        });
    });
});
