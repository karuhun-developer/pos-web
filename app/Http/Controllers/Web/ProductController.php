<?php

namespace App\Http\Controllers\Web;

use App\Actions\Catalog\DeleteProduct;
use App\Actions\Catalog\SaveProduct;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Support\MediaRef;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'category' => $request->query('category'),
            'status' => $request->query('status', 'all'),
        ];

        $products = Product::query()
            ->whereNull('deleted_at')
            ->when($filters['q'] !== '', fn ($query) => $query->where(
                fn ($q) => $q->where('name', 'like', "%{$filters['q']}%")
                    ->orWhere('sku', 'like', "%{$filters['q']}%")
                    ->orWhere('barcode', 'like', "%{$filters['q']}%"),
            ))
            ->when($filters['category'], fn ($query, $id) => $query->where('category_id', $id))
            ->when($filters['status'] === 'active', fn ($query) => $query->where('active', 1))
            ->when($filters['status'] === 'inactive', fn ($query) => $query->where('active', 0))
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        $images = MediaRef::urls($products->pluck('image_path'));

        $products->getCollection()->transform(function (Product $product) use ($images) {
            $product->setAttribute('image_url', $images[$product->image_path] ?? null);

            return $product;
        });

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $this->categories(),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Product::class);

        return Inertia::render('Products/Form', [
            'product' => null,
            'categories' => $this->categories(),
        ]);
    }

    public function store(ProductRequest $request, SaveProduct $save): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $save->handle($this->payload($request));

        return to_route('products.index')->with('success', 'Produk ditambahkan.');
    }

    public function edit(Product $product): Response
    {
        $this->authorize('update', $product);

        $images = MediaRef::urls([$product->image_path]);
        $product->setAttribute('image_url', $images[$product->image_path] ?? null);

        return Inertia::render('Products/Form', [
            'product' => $product,
            'categories' => $this->categories(),
        ]);
    }

    public function update(ProductRequest $request, Product $product, SaveProduct $save): RedirectResponse
    {
        $this->authorize('update', $product);

        $save->handle($this->payload($request), $product);

        return to_route('products.index')->with('success', 'Produk diperbarui.');
    }

    public function destroy(Product $product, DeleteProduct $delete): RedirectResponse
    {
        $this->authorize('delete', $product);

        $delete->handle($product);

        return to_route('products.index')->with('success', 'Produk dihapus.');
    }

    /**
     * File upload tidak ikut validated(), jadi digabung di sini — bukan
     * dibaca ulang dari request di dalam Action.
     *
     * @return array<string,mixed>
     */
    private function payload(ProductRequest $request): array
    {
        return [...$request->validated(), 'image' => $request->file('image')];
    }

    /** @return Collection<int,Category> */
    private function categories()
    {
        return Category::query()
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'color']);
    }
}
