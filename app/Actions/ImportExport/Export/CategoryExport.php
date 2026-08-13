<?php

namespace App\Actions\ImportExport\Export;

use App\Actions\ImportExport\Contracts\Exporter;
use App\Models\Category;
use App\Support\ReportPeriod;

class CategoryExport implements Exporter
{
    public function filename(ReportPeriod $period): string
    {
        return 'kategori';
    }

    public function headers(): array
    {
        return ['nama', 'warna', 'urutan'];
    }

    public function rows(ReportPeriod $period): iterable
    {
        foreach (Category::query()->whereNull('deleted_at')->orderBy('sort_order')->lazy() as $category) {
            yield [$category->name, $category->color, (int) $category->sort_order];
        }
    }
}
