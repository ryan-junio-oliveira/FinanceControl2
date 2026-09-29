<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Support\Fin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $family = Fin::family();
        $mes = Fin::month();

        $q = $family->categories()->with(['subcategories', 'transactions'])->withCount('subcategories');
        if ($request->filled('tipo') && in_array($request->tipo, ['despesa', 'receita'], true)) {
            $q->where('type', $request->tipo);
        }
        if ($request->boolean('arquivadas')) {
            $q->where('archived', true);
        } else {
            $q->where('archived', false);
        }

        $categorias = $q->orderBy('sort')->orderBy('name')->get()->map(function ($c) use ($mes) {
            $c->gasto_mes = $c->type === 'despesa' ? $c->spentInMonth($mes) : 0;
            $c->pct = $c->monthly_cap > 0 ? round($c->gasto_mes / (float) $c->monthly_cap * 100, 1) : 0;

            return $c;
        });

        $totalTetos = (float) $family->categories()->where('type', 'despesa')->where('archived', false)->sum('monthly_cap');
        $alertas = $categorias->filter(fn ($c) => $c->type === 'despesa' && $c->monthly_cap > 0 && $c->pct >= 90)->count();

        return view('pages.categorias', compact('mes', 'categorias', 'totalTetos', 'alertas'));
    }

    public function create(): View
    {
        return view('pages.categories.form', ['categoria' => null]);
    }

    public function edit(Category $categoria): View
    {
        abort_if($categoria->family_id !== Fin::familyId(), 404);

        return view('pages.categories.form', ['categoria' => $categoria->load('subcategories')]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $family = Fin::family();
        $data = $request->validated();

        $cat = $family->categories()->create([
            'name' => $data['name'],
            'type' => $data['type'],
            'icon' => $data['icon'] ?? 'tag',
            'monthly_cap' => $data['monthly_cap'] ?? null,
            'sort' => ($family->categories()->max('sort') ?? 0) + 1,
        ]);

        foreach ($this->parseSubs($data['subcategories'] ?? '') as $nome) {
            $cat->subcategories()->create(['name' => $nome]);
        }

        return redirect()->route('categorias')->with('status', 'Categoria criada.');
    }

    public function update(CategoryRequest $request, Category $categoria): RedirectResponse
    {
        $family = Fin::family();
        abort_if($categoria->family_id !== $family->id, 404);

        $data = $request->validated();
        $data['archived'] = $request->boolean('archived');
        $categoria->update($data);

        return redirect()->route('categorias')->with('status', 'Categoria atualizada.');
    }

    public function destroy(Category $categoria): RedirectResponse
    {
        $family = Fin::family();
        abort_if($categoria->family_id !== $family->id, 404);
        abort_if($categoria->transactions()->exists(), 422, 'Categoria com lançamentos não pode ser excluída. Arquive-a.');
        $categoria->delete();

        return redirect()->route('categorias')->with('status', 'Categoria excluída.');
    }

    /** @return string[] */
    private function parseSubs(string $raw): array
    {
        return collect(preg_split('/[\r\n,;]+/', $raw))->map(fn ($s) => trim($s))->filter()->take(20)->values()->all();
    }
}
