<?php

namespace App\Http\Middleware;

use App\Models\Account;
use App\Models\Asset;
use App\Models\CardTransaction;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Invitation;
use App\Models\Portfolio;
use App\Models\Transaction;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garante que o recurso ({account}, {card}, {transaction}...) pertence
 * à grupo do usuário autenticado.
 *
 * Uso: Route::middleware('group.ownership:account') — o nome do parâmetro
 * de rota é passado ao middleware, que resolve o model e valida o group_id.
 */
class EnsureGroupOwnership
{
    /** @var array<string, class-string<Model>> */
    private const MODELS = [
        'account' => Account::class,
        'card' => CreditCard::class,
        'transaction' => Transaction::class,
        'category' => Category::class,
        'asset' => Asset::class,
        'portfolio' => Portfolio::class,
        'invite' => Invitation::class,
        'member' => User::class,
        'item' => CardTransaction::class,
    ];

    public function handle(Request $request, Closure $next, string $param): Response
    {
        $model = $request->route($param);

        if (! $model instanceof Model) {
            abort(404);
        }

        $class = self::MODELS[$param] ?? null;
        if ($class && ! $model instanceof $class) {
            abort(404);
        }

        $groupId = $request->user()?->group_id;
        abort_if($groupId === null || $model->group_id !== $groupId, 404);

        return $next($request);
    }
}
