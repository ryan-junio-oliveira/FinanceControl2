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

        // Listagem leve: apenas cadastro, sem cálculos.
        $q = $family->categories();
        if ($request->filled('tipo') && in_array($request->tipo, ['despesa', 'receita'], true)) {
            $q->where('type', $request->tipo);
        }
        if ($request->filled('q')) {
            $q->where('name', 'like', '%'.$request->q.'%');
        }
        if ($request->boolean('arquivadas')) {
            $q->where('archived', true);
        } else {
            $q->where('archived', false);
        }

        $categorias = $q->orderBy('sort')->orderBy('name')->paginate(20)->withQueryString();

        return view('pages.categorias', compact('categorias'));
    }

    public function create(): View
    {
        return view('pages.categories.form', ['category' => null]);
    }

    public function edit(Category $category): View
    {
        abort_if($category->family_id !== Fin::familyId(), 404);
        $this->authorize('manage', $category);

        return view('pages.categories.form', ['category' => $category]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $family = Fin::family();
        $data = $request->validated();

        $family->categories()->create([
            'name' => $data['name'],
            'type' => $data['type'],
            'icon' => $data['icon'] ?? 'tag',
            'sort' => ($family->categories()->max('sort') ?? 0) + 1,
        ]);

        return redirect()->route('categorias')->with('status', 'Categoria criada.');
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $family = Fin::family();
        abort_if($category->family_id !== $family->id, 404);
        $this->authorize('manage', $category);

        $data = $request->validated();
        $data['archived'] = $request->boolean('archived');
        $category->update($data);

        return redirect()->route('categorias')->with('status', 'Categoria atualizada.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $family = Fin::family();
        abort_if($category->family_id !== $family->id, 404);
        $this->authorize('manage', $category);
        abort_if($category->transactions()->exists(), 422, 'Categoria com lançamentos não pode ser excluída. Arquive-a.');
        $category->delete();

        return redirect()->route('categorias')->with('status', 'Categoria excluída.');
    }
}
