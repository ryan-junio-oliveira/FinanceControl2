<?php

use App\Http\Controllers\AccountController;
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
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TransactionController;
use App\Support\Fin;
use Illuminate\Support\Facades\Route;

// ---------- Visitantes ----------
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.attempt');

    Route::get('/cadastro', [RegisteredUserController::class, 'create'])->name('cadastro');
    Route::post('/cadastro', [RegisteredUserController::class, 'store'])->name('cadastro.store');

    Route::get('/recuperar-senha', [PasswordResetLinkController::class, 'create'])->name('password.recuperar');
    Route::post('/recuperar-senha', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/redefinir-senha/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/redefinir-senha', [NewPasswordController::class, 'store'])->name('password.update');

    Route::get('/primeiro-acesso/{token}', [FirstAccessController::class, 'show'])->name('password.primeiro-acesso');
    Route::post('/primeiro-acesso/{token}', [FirstAccessController::class, 'store'])->name('password.primeiro-acesso.store');
});

// ---------- Autenticados ----------
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware('family.role')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

        // Perfil do usuário
        Route::get('/perfil', [ProfileController::class, 'show'])->name('perfil');
        Route::get('/perfil/editar', [ProfileController::class, 'edit'])->name('perfil.edit');
        Route::patch('/perfil', [ProfileController::class, 'update'])->name('perfil.update');
        Route::get('/perfil/senha', [ProfileController::class, 'editPassword'])->name('perfil.senha');
        Route::patch('/perfil/senha', [ProfileController::class, 'updatePassword'])->name('perfil.senha.update');

        // Despesas & Receitas
        Route::get('/despesas', [TransactionController::class, 'index'])->defaults('type', 'despesa')->name('despesas');
        Route::get('/receitas', [TransactionController::class, 'index'])->defaults('type', 'receita')->name('receitas');
        Route::get('/despesas/criar', [TransactionController::class, 'create'])->defaults('type', 'despesa')->name('despesas.create');
        Route::get('/receitas/criar', [TransactionController::class, 'create'])->defaults('type', 'receita')->name('receitas.create');
        Route::get('/lancamentos/{transaction}/editar', [TransactionController::class, 'edit'])->name('lancamentos.edit');
        Route::post('/despesas', [TransactionController::class, 'store'])->defaults('type', 'despesa')->name('despesas.store');
        Route::post('/receitas', [TransactionController::class, 'store'])->defaults('type', 'receita')->name('receitas.store');
        Route::patch('/lancamentos/{transaction}', [TransactionController::class, 'update'])->name('lancamentos.update');
        Route::post('/lancamentos/{transaction}/liquidar', [TransactionController::class, 'settle'])->name('lancamentos.settle');

        // Contas
        Route::get('/contas', [AccountController::class, 'index'])->name('contas');
        Route::get('/contas/transferir', [AccountController::class, 'createTransfer'])->name('contas.transfer.create');

        // Cartões (fatura p/ todos)
        Route::get('/cartoes', [CardController::class, 'index'])->name('cartoes');
        Route::get('/cartoes/itens/criar', [CardController::class, 'createItem'])->name('cartoes.itens.create');
        Route::post('/cartoes/itens', [CardController::class, 'storeItem'])->name('cartoes.itens.store');
        Route::post('/cartoes/itens/{item}/liquidar', [CardController::class, 'settleItem'])->name('cartoes.itens.settle');

        // Categorias (leitura p/ todos)
        Route::get('/categorias', [CategoryController::class, 'index'])->name('categorias');

        // Família (leitura p/ todos)
        Route::get('/familia', [FamilyController::class, 'index'])->name('familia');

        // ----- Gestores (admin / co_admin) -----
        Route::middleware('family.role:admin,co_admin')->group(function () {
            Route::delete('/lancamentos/{transaction}', [TransactionController::class, 'destroy'])->name('lancamentos.destroy');

            Route::get('/contas/criar', [AccountController::class, 'create'])->name('contas.create');
            Route::post('/contas', [AccountController::class, 'store'])->name('contas.store');
            Route::get('/contas/{conta}/editar', [AccountController::class, 'edit'])->name('contas.edit');
            Route::patch('/contas/{conta}', [AccountController::class, 'update'])->name('contas.update');
            Route::delete('/contas/{conta}', [AccountController::class, 'destroy'])->name('contas.destroy');
            Route::post('/contas/transferir', [AccountController::class, 'transfer'])->name('contas.transfer');

            Route::get('/cartoes/criar', [CardController::class, 'create'])->name('cartoes.create');
            Route::post('/cartoes', [CardController::class, 'store'])->name('cartoes.store');
            Route::get('/cartoes/{cartao}/editar', [CardController::class, 'edit'])->name('cartoes.edit');
            Route::patch('/cartoes/{cartao}', [CardController::class, 'update'])->name('cartoes.update');
            Route::delete('/cartoes/{cartao}', [CardController::class, 'destroy'])->name('cartoes.destroy');

            Route::get('/categorias/criar', [CategoryController::class, 'create'])->name('categorias.create');
            Route::post('/categorias', [CategoryController::class, 'store'])->name('categorias.store');
            Route::get('/categorias/{categoria}/editar', [CategoryController::class, 'edit'])->name('categorias.edit');
            Route::patch('/categorias/{categoria}', [CategoryController::class, 'update'])->name('categorias.update');
            Route::delete('/categorias/{categoria}', [CategoryController::class, 'destroy'])->name('categorias.destroy');

            Route::get('/investimentos', [InvestmentController::class, 'index'])->name('investimentos');
            Route::get('/investimentos/carteiras/criar', [InvestmentController::class, 'createPortfolio'])->name('investimentos.carteiras.create');
            Route::post('/investimentos/carteiras', [InvestmentController::class, 'storePortfolio'])->name('investimentos.carteiras.store');
            Route::get('/investimentos/ativos/criar', [InvestmentController::class, 'createAsset'])->name('investimentos.ativos.create');
            Route::post('/investimentos/ativos', [InvestmentController::class, 'storeAsset'])->name('investimentos.ativos.store');
            Route::get('/investimentos/aportes/criar', [InvestmentController::class, 'createContribution'])->name('investimentos.aportes.create');
            Route::post('/investimentos/aportes', [InvestmentController::class, 'storeContribution'])->name('investimentos.aportes.store');
            Route::delete('/investimentos/ativos/{ativo}', [InvestmentController::class, 'destroyAsset'])->name('investimentos.ativos.destroy');

            Route::get('/familia/convites/criar', [FamilyController::class, 'createInvite'])->name('familia.convites.create');
            Route::post('/familia/convites', [FamilyController::class, 'invite'])->name('familia.convites.store');
            Route::delete('/familia/convites/{convite}', [FamilyController::class, 'revokeInvite'])->name('familia.convites.destroy');
            Route::delete('/familia/membros/{membro}', [FamilyController::class, 'removeMember'])->name('familia.membros.destroy');
            Route::patch('/familia/membros/{membro}/papel', [FamilyController::class, 'updateRole'])->name('familia.membros.papel');
            Route::get('/familia/mesadas/criar', [FamilyController::class, 'createAllowance'])->name('familia.mesadas.create');
            Route::post('/familia/mesadas', [FamilyController::class, 'storeAllowance'])->name('familia.mesadas.store');
            Route::delete('/familia/mesadas/{allowance}', [FamilyController::class, 'destroyAllowance'])->name('familia.mesadas.destroy');

            Route::post('/cartoes/{cartao}/pagar-fatura', [CardController::class, 'payInvoice'])->name('cartoes.fatura.pagar');

            Route::get('/configuracoes', [SettingsController::class, 'index'])->name('configuracoes');
            Route::patch('/configuracoes', [SettingsController::class, 'update'])->name('configuracoes.update');
            Route::patch('/configuracoes/notificacoes', [SettingsController::class, 'updateNotifications'])->name('configuracoes.notificacoes');
            Route::get('/configuracoes/bancos/criar', [SettingsController::class, 'createBank'])->name('configuracoes.bancos.create');
            Route::post('/configuracoes/bancos', [SettingsController::class, 'storeBank'])->name('configuracoes.bancos.store');
            Route::delete('/configuracoes/bancos/{banco}', [SettingsController::class, 'destroyBank'])->name('configuracoes.bancos.destroy');
            Route::post('/configuracoes/encerrar-sessoes', [SettingsController::class, 'logoutOthers'])->name('configuracoes.sessoes');
        });
    });
});
