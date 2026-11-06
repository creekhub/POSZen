<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportProductSalesProductOptionsController
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'term' => ['required', 'string', 'min:2', 'max:100'],
            'categories' => ['sometimes', 'array', 'max:100'],
            'categories.*' => ['integer', 'exists:ProductGroup,Id'],
            'excludeTrackedFavorites' => ['sometimes', 'boolean'],
        ]);

        $categoryIds = collect($validated['categories'] ?? [])
            ->map(fn ($categoryId) => (int) $categoryId)
            ->unique()
            ->all();
        $search = '%'.trim($validated['term']).'%';
        $excludeTrackedFavorites = (bool) ($validated['excludeTrackedFavorites'] ?? false);

        $results = DB::table('Product as product')
            ->leftJoin('Barcode as barcode', 'barcode.ProductId', '=', 'product.Id')
            ->where(function ($query) use ($search): void {
                $query->where('product.Name', 'like', $search)
                    ->orWhere('product.Code', 'like', $search)
                    ->orWhere('barcode.Value', 'like', $search);
            })
            ->when($categoryIds !== [], fn ($query) => $query->whereIn('product.ProductGroupId', $categoryIds))
            ->when($excludeTrackedFavorites, fn ($query) => $query->whereNotIn(
                'product.Id',
                DB::table('TrackFavoriteProducts')->whereNotNull('ProductId')->select('ProductId')
            ))
            ->orderBy('product.Name')
            ->limit(20)
            ->get(['product.Id', 'product.Name', 'product.Code'])
            ->map(fn ($product) => [
                'id' => (string) $product->Id,
                'text' => $product->Name,
                '_resultId' => 'product-report-products-option-'.$product->Id,
            ])
            ->all();

        return response()->json(['results' => $results]);
    }
}
