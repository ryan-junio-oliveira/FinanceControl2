<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuditController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CardController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\GroupController;
use App\Http\Controllers\Api\V1\InvestmentController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\BotWebhookController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — Prumo
|--------------------------------------------------------------------------
| REST para o bot (smartphone). Autenticação via Sanctum (Bearer token).
| Middleware `group.ownership` garante que o recurso pertence à grupo.
*/

// Webhook do bot (público; validado pelo segredo do canal)
Route::post('/bot/telegram', [BotWebhookController::class, 'telegram'])->middleware('throttle:120,1')->name('bot.telegram');

Route::prefix('v1')->group(function () {
    // Autenticação por token
    Route::post('/auth/token', [AuthController::class, 'token'])->middleware('throttle:10,1')->name('api.auth.token');

    Route::middleware('auth:sanctum')->group(function () {
        Route::delete('/auth/token', [AuthController::class, 'logout'])->name('api.auth.logout');
        Route::get('/user', fn (Request $request) => new UserResource($request->user()))->name('api.user');

        Route::middleware(['plan.active', 'throttle:120,1'])->group(function () {
            // Lançamentos (despesas/receitas)
            Route::get('/transactions/{type}', [TransactionController::class, 'index'])->name('api.transactions.index');
            Route::post('/transactions/{type}', [TransactionController::class, 'store'])->name('api.transactions.store');
            Route::get('/transactions/{type}/{transaction}', [TransactionController::class, 'show'])->middleware('group.ownership:transaction')->name('api.transactions.show');
            Route::patch('/transactions/{type}/{transaction}', [TransactionController::class, 'update'])->middleware('group.ownership:transaction')->name('api.transactions.update');
            Route::delete('/transactions/{type}/{transaction}', [TransactionController::class, 'destroy'])->middleware(['group.ownership:transaction', 'group.role:admin,co_admin'])->name('api.transactions.destroy');
            Route::post('/transactions/{type}/{transaction}/settle', [TransactionController::class, 'settle'])->middleware('group.ownership:transaction')->name('api.transactions.settle');

            // Contas
            Route::get('/accounts', [AccountController::class, 'index'])->name('api.accounts.index');
            Route::post('/accounts', [AccountController::class, 'store'])->middleware('group.role:admin,co_admin')->name('api.accounts.store');
            Route::get('/accounts/{account}', [AccountController::class, 'show'])->middleware('group.ownership:account')->name('api.accounts.show');
            Route::patch('/accounts/{account}', [AccountController::class, 'update'])->middleware(['group.ownership:account', 'group.role:admin,co_admin'])->name('api.accounts.update');
            Route::delete('/accounts/{account}', [AccountController::class, 'destroy'])->middleware(['group.ownership:account', 'group.role:admin,co_admin'])->name('api.accounts.destroy');
            Route::post('/accounts/transfer', [AccountController::class, 'transfer'])->middleware('group.role:admin,co_admin')->name('api.accounts.transfer');

            // Cartões
            Route::get('/cards', [CardController::class, 'index'])->name('api.cards.index');
            Route::post('/cards', [CardController::class, 'store'])->middleware('group.role:admin,co_admin')->name('api.cards.store');
            Route::get('/cards/{card}', [CardController::class, 'show'])->middleware('group.ownership:card')->name('api.cards.show');
            Route::patch('/cards/{card}', [CardController::class, 'update'])->middleware(['group.ownership:card', 'group.role:admin,co_admin'])->name('api.cards.update');
            Route::delete('/cards/{card}', [CardController::class, 'destroy'])->middleware(['group.ownership:card', 'group.role:admin,co_admin'])->name('api.cards.destroy');
            Route::post('/cards/items', [CardController::class, 'storeItem'])->name('api.cards.items.store');
            Route::post('/cards/items/{item}/settle', [CardController::class, 'settleItem'])->middleware('group.ownership:item')->name('api.cards.items.settle');
            Route::post('/cards/{card}/pay-invoice', [CardController::class, 'payInvoice'])->middleware(['group.ownership:card', 'group.role:admin,co_admin'])->name('api.cards.pay-invoice');

            // Categorias
            Route::get('/categories', [CategoryController::class, 'index'])->name('api.categories.index');
            Route::post('/categories', [CategoryController::class, 'store'])->middleware('group.role:admin,co_admin')->name('api.categories.store');
            Route::get('/categories/{category}', [CategoryController::class, 'show'])->middleware('group.ownership:category')->name('api.categories.show');
            Route::patch('/categories/{category}', [CategoryController::class, 'update'])->middleware(['group.ownership:category', 'group.role:admin,co_admin'])->name('api.categories.update');
            Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware(['group.ownership:category', 'group.role:admin,co_admin'])->name('api.categories.destroy');

            // Investimentos
            Route::get('/assets', [InvestmentController::class, 'assets'])->name('api.assets.index');
            Route::post('/assets', [InvestmentController::class, 'storeAsset'])->middleware('group.role:admin,co_admin')->name('api.assets.store');
            Route::patch('/assets/{asset}', [InvestmentController::class, 'updateAsset'])->middleware(['group.ownership:asset', 'group.role:admin,co_admin'])->name('api.assets.update');
            Route::delete('/assets/{asset}', [InvestmentController::class, 'destroyAsset'])->middleware(['group.ownership:asset', 'group.role:admin,co_admin'])->name('api.assets.destroy');
            Route::post('/contributions', [InvestmentController::class, 'storeContribution'])->middleware('group.role:admin,co_admin')->name('api.contributions.store');

            // Grupo
            Route::get('/group/members', [GroupController::class, 'members'])->name('api.group.members');
            Route::get('/group/invites', [GroupController::class, 'invites'])->name('api.group.invites');
            Route::post('/group/invites', [GroupController::class, 'storeInvite'])->middleware('group.role:admin,co_admin')->name('api.group.invites.store');
            Route::delete('/group/invites/{invite}', [GroupController::class, 'revokeInvite'])->middleware(['group.ownership:invite', 'group.role:admin,co_admin'])->name('api.group.invites.destroy');
            Route::delete('/group/members/{member}', [GroupController::class, 'removeMember'])->middleware(['group.ownership:member', 'group.role:admin,co_admin'])->name('api.group.members.destroy');
            Route::patch('/group/members/{member}/role', [GroupController::class, 'updateRole'])->middleware(['group.ownership:member', 'group.role:admin,co_admin'])->name('api.group.members.role');

            // Dashboard
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('api.dashboard');

            // Logs de auditoria (somente admin)
            Route::get('/admin/logs', [AuditController::class, 'index'])->name('api.admin.logs');
        });
    });
});
