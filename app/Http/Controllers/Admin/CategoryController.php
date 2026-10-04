<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** MODULE 1 — CRUD Catégories. */
class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Category::withCount('equipment')
            ->when($request->q, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->orderBy('name')->paginate(10)->withQueryString();

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => new Category(['icon' => 'bi-sun'])]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated() + ['slug' => Str::slug($request->name)]);

        return redirect()->route('admin.categories.index')->with('success', 'Catégorie créée.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated() + ['slug' => Str::slug($request->name)]);

        return redirect()->route('admin.categories.index')->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->equipment()->exists()) {
            return back()->with('error', 'Suppression impossible : des équipements utilisent cette catégorie.');
        }

        $category->delete();

        return back()->with('success', 'Catégorie supprimée.');
    }
}
