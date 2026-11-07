<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReportFavoriteProducts extends Component
{
    private const PRODUCT_TABLE = 'Product as product';

    public array $selectedProductIds = [];

    public string $startDate = '';

    public string $endDate = '';

    public function mount(): void
    {
        $today = Carbon::today();
        $this->startDate = $today->copy()->subDays(6)->toDateString();
        $this->endDate = $today->toDateString();
    }

    public function updatedStartDate(): void
    {
        $this->refreshChart();
    }

    public function updatedEndDate(): void
    {
        $this->refreshChart();
    }

    public function addProducts(): void
    {
        $validated = $this->validate([
            'selectedProductIds' => ['required', 'array', 'min:1', 'max:100'],
            'selectedProductIds.*' => ['required', 'integer', 'distinct', 'exists:Product,Id'],
        ]);
        $requestedIds = collect($validated['selectedProductIds'])
            ->map(fn ($productId) => (int) $productId)
            ->unique()
            ->values();
        $eligibleIds = DB::table('Product')
            ->whereIn('Id', $requestedIds)
            ->whereNotIn('Id', DB::table('TrackFavoriteProducts')->whereNotNull('ProductId')->select('ProductId'))
            ->pluck('Id')
            ->map(fn ($productId) => (int) $productId);

        if ($eligibleIds->count() !== $requestedIds->count()) {
            $this->addError('selectedProductIds', 'One or more selected products are already being tracked.');

            return;
        }

        DB::table('TrackFavoriteProducts')->insert(
            $eligibleIds->map(fn ($productId) => ['ProductId' => $productId])->all()
        );
        $this->selectedProductIds = [];
        $this->resetValidation('selectedProductIds');
        $this->dispatch('favorite-products-added');
        $this->refreshChart();
    }

    public function removeProduct(int $productId): void
    {
        DB::table('TrackFavoriteProducts')->where('ProductId', $productId)->delete();
        $this->refreshChart();
    }

    public function render(): View
    {
        $favoriteProducts = DB::table('TrackFavoriteProducts as favorite')
            ->join(self::PRODUCT_TABLE, 'product.Id', '=', 'favorite.ProductId')
            ->orderBy('product.Name')
            ->get(['product.Id', 'product.Name', 'product.Code']);
        $chart = $this->buildChart();

        foreach ($favoriteProducts as $product) {
            $totals = $chart['productTotals'][$product->Id] ?? ['sales' => 0.0, 'profit' => 0.0];
            $product->totalSales = $totals['sales'];
            $product->totalProfit = $totals['profit'];
        }

        return view('livewire.report-favorite-products', [
            'favoriteProducts' => $favoriteProducts,
            'chart' => $chart,
        ]);
    }

    private function refreshChart(): void
    {
        $chart = $this->buildChart();
        $this->dispatch(
            'favorite-products-trend-updated',
            labels: $chart['labels'],
            datasets: $chart['datasets'],
        );
    }

    private function buildChart(): array
    {
        $startDate = Carbon::parse($this->startDate ?: Carbon::today()->toDateString())->startOfDay();
        $endDate = Carbon::parse($this->endDate ?: $startDate->toDateString())->startOfDay();

        if ($endDate->lessThan($startDate)) {
            [$startDate, $endDate] = [$endDate->copy(), $startDate->copy()];
        }

        $labels = [];
        $keys = [];
        for ($date = $startDate->copy(); $date->lessThanOrEqualTo($endDate); $date->addDay()) {
            $keys[] = $date->toDateString();
            $labels[] = $date->format('M j');
        }

        $favoriteProducts = DB::table('TrackFavoriteProducts as favorite')
            ->join(self::PRODUCT_TABLE, 'product.Id', '=', 'favorite.ProductId')
            ->orderBy('product.Name')
            ->get(['product.Id', 'product.Name']);
        $favoriteProductIds = $favoriteProducts->pluck('Id');
        $aggregates = collect();

        if ($favoriteProductIds->isNotEmpty()) {
            $documentSubtotals = DB::table('DocumentItem as discount_item')
                ->join('Document as subtotal_document', 'subtotal_document.Id', '=', 'discount_item.DocumentId')
                ->where('subtotal_document.DocumentTypeId', 2)
                ->whereBetween('subtotal_document.DateCreated', [$startDate, $endDate->copy()->endOfDay()])
                ->select('discount_item.DocumentId')
                ->selectRaw('COALESCE(SUM(COALESCE(discount_item.TotalAfterDocumentDiscount, discount_item.Total, discount_item.PriceAfterDiscount * discount_item.Quantity)), 0) as subtotal')
                ->groupBy('discount_item.DocumentId');
            $lineAmount = 'COALESCE(item.TotalAfterDocumentDiscount, item.Total, item.PriceAfterDiscount * item.Quantity)';
            $documentSubtotal = 'COALESCE(document_subtotal.subtotal, 0)';
            $effectiveOrderDiscount = '(CASE WHEN COALESCE(document.Discount, 0) > '.$documentSubtotal.' THEN '.$documentSubtotal.' ELSE COALESCE(document.Discount, 0) END)';
            $orderDiscountAllocation = '('.$effectiveOrderDiscount.' * '.$lineAmount.' / NULLIF('.$documentSubtotal.', 0))';
            $netAmount = '(CASE WHEN '.$lineAmount.' <= '.$orderDiscountAllocation.' THEN 0 ELSE '.$lineAmount.' - '.$orderDiscountAllocation.' END)';
            $productCost = 'COALESCE(NULLIF(item.ProductCost, 0), NULLIF(product.Cost, 0), product.LastPurchasePrice, 0)';

            $aggregates = DB::table('DocumentItem as item')
                ->join('Document as document', 'document.Id', '=', 'item.DocumentId')
                ->join(self::PRODUCT_TABLE, 'product.Id', '=', 'item.ProductId')
                ->leftJoinSub($documentSubtotals, 'document_subtotal', 'document_subtotal.DocumentId', '=', 'document.Id')
                ->where('document.DocumentTypeId', 2)
                ->whereIn('item.ProductId', $favoriteProductIds)
                ->whereBetween('document.DateCreated', [$startDate, $endDate->copy()->endOfDay()])
                ->select('item.ProductId as product_id')
                ->selectRaw('DATE(document.DateCreated) as sale_date')
                ->selectRaw('COALESCE(SUM('.$netAmount.'), 0) as total_sales')
                ->selectRaw('COALESCE(SUM(CASE WHEN '.$productCost.' > 0 THEN ('.$netAmount.') - ('.$productCost.') * item.Quantity ELSE 0 END), 0) as total_profit')
                ->groupBy('item.ProductId')
                ->groupByRaw('DATE(document.DateCreated)')
                ->get()
                ->groupBy('product_id')
                ->map(fn ($productAggregates) => $productAggregates->keyBy('sale_date'));
        }

        $datasets = [];
        $productTotals = [];
        $totalSales = 0.0;
        $totalProfit = 0.0;
        foreach ($favoriteProducts as $productIndex => $product) {
            $productAggregates = $aggregates->get($product->Id, collect());
            $salesValues = [];
            $profitValues = [];

            foreach ($keys as $key) {
                $salesValues[] = (float) ($productAggregates->get($key)->total_sales ?? 0);
                $profitValues[] = (float) ($productAggregates->get($key)->total_profit ?? 0);
            }

            $productSales = array_sum($salesValues);
            $productProfit = array_sum($profitValues);
            $productTotals[$product->Id] = [
                'sales' => $productSales,
                'profit' => $productProfit,
            ];
            $totalSales += $productSales;
            $totalProfit += $productProfit;
            $datasets[] = [
                'label' => $product->Name,
                'productIndex' => $productIndex,
                'metric' => 'sales',
                'data' => $salesValues,
            ];
            $datasets[] = [
                'label' => $product->Name,
                'productIndex' => $productIndex,
                'metric' => 'profit',
                'data' => $profitValues,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
            'productTotals' => $productTotals,
            'totalSales' => $totalSales,
            'totalProfit' => $totalProfit,
        ];
    }
}
