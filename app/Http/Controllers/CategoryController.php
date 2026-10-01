<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use App\Support\Fin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request, CategoryService $service): View
    {
        $categorias = $service->list(Fin::family(), [
            'tipo' => $request->query('tipo'),
            'q' => $request->query('q'),
            'arquivadas' => $request->boolean('arquivadas'),
        ])->withQueryString();

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

    public function store(CategoryRequest $request, CategoryService $service): RedirectResponse
    {
        $family = Fin::family();
        $service->create($family, $request->validated());

        return redirect()->route('categorias')->with('status', 'Categoria criada.');
    }

    public function update(CategoryRequest $request, Category $category, CategoryService $service): RedirectResponse
    {
        $family = Fin::family();
        abort_if($category->family_id !== $family->id, 404);
        $this->authorize('manage', $category);

        $service->update($category, $request->validated(), $request->boolean('archived'));

        return redirect()->route('categorias')->with('status', 'Categoria atualizada.');
    }

    public function destroy(Category $category, CategoryService $service): RedirectResponse
    {
        $family = Fin::family();
        abort_if($category->family_id !== $family->id, 404);
        $this->authorize('manage', $category);
        $service->destroy($category);

        return redirect()->route('categorias')->with('status', 'Categoria excluída.');
    }
}
