<?php

namespace App\Http\Controllers\Web;

use App\Actions\Catalog\DeleteCategory;
use App\Actions\Catalog\SaveCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CategoryRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Category::class);

        // Jumlah produk per kategori dihitung sekali, bukan per baris di view.
        $counts = Product::query()
            ->whereNull('deleted_at')
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->selectRaw('category_id, count(*) as total')
            ->pluck('total', 'category_id');

        $categories = Category::query()
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'color' => $category->color,
                'sort_order' => $category->sort_order,
                'products_count' => (int) ($counts[$category->id] ?? 0),
            ]);

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
            'uncategorized_count' => Product::query()
                ->whereNull('deleted_at')
                ->whereNull('category_id')
                ->count(),
        ]);
    }

    public function store(CategoryRequest $request, SaveCategory $save): RedirectResponse
    {
        $this->authorize('create', Category::class);

        $save->handle($request->validated());

        return back()->with('success', 'Kategori ditambahkan.');
    }

    public function update(CategoryRequest $request, Category $category, SaveCategory $save): RedirectResponse
    {
        $this->authorize('update', $category);

        $save->handle($request->validated(), $category);

        return back()->with('success', 'Kategori diperbarui.');
    }

    public function destroy(Category $category, DeleteCategory $delete): RedirectResponse
    {
        $this->authorize('delete', $category);

        $delete->handle($category);

        return back()->with('success', 'Kategori dihapus.');
    }
}
