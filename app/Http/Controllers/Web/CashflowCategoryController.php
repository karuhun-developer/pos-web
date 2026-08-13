<?php

namespace App\Http\Controllers\Web;

use App\Actions\Cashflow\DeleteCategory;
use App\Actions\Cashflow\SaveCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CashflowCategoryRequest;
use App\Models\CashflowCategory;
use Illuminate\Http\RedirectResponse;

class CashflowCategoryController extends Controller
{
    public function store(CashflowCategoryRequest $request, SaveCategory $save): RedirectResponse
    {
        $this->authorize('create', CashflowCategory::class);

        $save->handle($request->validated());

        return back()->with('success', 'Kategori kas ditambahkan.');
    }

    public function update(
        CashflowCategoryRequest $request,
        CashflowCategory $category,
        SaveCategory $save,
    ): RedirectResponse {
        $this->authorize('update', $category);

        $save->handle($request->validated(), $category);

        return back()->with('success', 'Kategori kas diperbarui.');
    }

    public function destroy(CashflowCategory $category, DeleteCategory $delete): RedirectResponse
    {
        $this->authorize('delete', $category);

        $delete->handle($category);

        return back()->with('success', 'Kategori kas dihapus.');
    }
}
