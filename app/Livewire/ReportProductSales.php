<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Async;
use Livewire\Component;

class ReportProductSales extends Component
{
    public string $productSearch = '';
    public array $productSuggestions = [];
    public array $selectedProductIds = [];
    public array $selectedProductNames = [];
    public string $productStartDate = '';
    public string $productEndDate = '';
    public array $productReport = [];

    public function mount(): void
    {
        $today = Carbon::today();
        $this->productStartDate = $today->copy()->subDays(6)->toDateString();
        $this->productEndDate = $today->toDateString();
        $this->productReport = [
            'productOptions' => [],
            'selectedProducts' => [],
            'products' => [],
            'trend' => [],
            'profitTrend' => [],
            'startDate' => $this->productStartDate,
            'endDate' => $this->productEndDate,
            'totalAmount' => 0,
            'totalProfit' => 0,
            'profitTotal' => 0,
            'totalDiscount' => 0,
            'totalQuantity' => 0,
        ];
    }

    public function updatedProductSearch(string $value): void
    {
        $search = trim($value);

        if (mb_strlen($search) < 2) {
            $this->productSuggestions = [];
            return;
        }

        $search = '%'.$search.'%';
        $this->productSuggestions = DB::table('Product')
            ->where(function ($query) use ($search): void {
                $query->where('Name', 'like', $search)
                    ->orWhere('Code', 'like', $search);
            })
            ->orderBy('Name')
            ->limit(12)
            ->get(['Id', 'Name', 'Code'])
            ->map(fn ($product) => (array) $product)
            ->all();
    }

    public function addProductToSearch(int $productId): void
    {
        $product = collect($this->productSuggestions)->firstWhere('Id', $productId);

        if ($product && ! collect($this->selectedProductIds)->contains(fn ($id) => (int) $id === $productId)) {
            $this->selectedProductIds[] = $productId;
            $this->selectedProductNames[] = [
                'Id' => $productId,
                'Name' => $product['Name'],
            ];
        }

        $this->productSearch = '';
        $this->productSuggestions = [];
    }

    public function removeProductFromSearch(int $productId): void
    {
        $this->selectedProductIds = array_values(array_filter(
            $this->selectedProductIds,
            fn ($selectedId) => (int) $selectedId !== $productId,
        ));
        $this->selectedProductNames = array_values(array_filter(
            $this->selectedProductNames,
            fn ($product) => (int) $product['Id'] !== $productId,
        ));
    }

    #[Async]
    public function searchProducts(): void
    {
        if ($this->selectedProductIds === []) {
            $this->selectedProductIds = array_map(
                fn ($product) => (int) $product['Id'],
                $this->productSuggestions,
            );
        }

        $this->validate([
            'productSearch' => ['nullable', 'string', 'max:255'],
            'selectedProductIds' => ['array', 'max:100'],
            'selectedProductIds.*' => ['integer', 'exists:Product,Id'],
            'productStartDate' => ['required', 'date'],
            'productEndDate' => ['required', 'date', 'after_or_equal:productStartDate'],
        ]);

        $this->productReport = $this->buildProductReport();
        $this->selectedProductIds = array_column($this->productReport['selectedProducts'], 'Id');
        $this->selectedProductNames = $this->productReport['selectedProducts'];
        $this->dispatch(
            'product-report-updated',
            labels: $this->productReport['trend']['labels'],
            datasets: $this->productReport['trend']['datasets'],
            profitLabels: $this->productReport['profitTrend']['labels'],
            profitValues: $this->productReport['profitTrend']['values'],
            profitTotal: $this->productReport['profitTotal'],
        );
    }

    public function render(): View
    {
        return view('livewire.report-product-sales', [
            'productReport' => $this->productReport,
        ]);
    }

    private function buildProductReport(): array
    {
        $startDate = Carbon::parse($this->productStartDate)->startOfDay();
        $endDate = Carbon::parse($this->productEndDate)->startOfDay();

        if ($endDate->lessThan($startDate)) {
            [$startDate, $endDate] = [$endDate->copy(), $startDate->copy()];
        }

        $selectedProductIds = collect($this->selectedProductIds)
            ->filter(fn ($productId) => filter_var($productId, FILTER_VALIDATE_INT) !== false && (int) $productId > 0)
            ->map(fn ($productId) => (int) $productId)
            ->unique()
            ->values();

        $selectedProducts = $selectedProductIds->isNotEmpty()
            ? DB::table('Product')->whereIn('Id', $selectedProductIds)->orderBy('Name')->get(['Id', 'Name'])->map(fn ($product) => (array) $product)->all()
            : [];

        $products = [];
        $dailyQuantities = collect();
        $lineAmount = 'COALESCE(item.TotalAfterDocumentDiscount, item.Total, item.PriceAfterDiscount * item.Quantity)';
        $documentSubtotal = '(SELECT COALESCE(SUM(COALESCE(discount_item.TotalAfterDocumentDiscount, discount_item.Total, discount_item.PriceAfterDiscount * discount_item.Quantity)), 0) FROM DocumentItem as discount_item WHERE discount_item.DocumentId = document.Id)';
        $effectiveOrderDiscount = '(CASE WHEN COALESCE(document.Discount, 0) > '.$documentSubtotal.' THEN '.$documentSubtotal.' ELSE COALESCE(document.Discount, 0) END)';
        $orderDiscountAllocation = '('.$effectiveOrderDiscount.' * '.$lineAmount.' / NULLIF('.$documentSubtotal.', 0))';
        $netAmount = '(CASE WHEN '.$lineAmount.' <= '.$orderDiscountAllocation.' THEN 0 ELSE '.$lineAmount.' - '.$orderDiscountAllocation.' END)';
        $productCost = 'COALESCE(NULLIF(item.ProductCost, 0), NULLIF(product.Cost, 0), product.LastPurchasePrice, 0)';

        $dailyProfit = DB::table('DocumentItem as item')
            ->join('Document as document', 'document.Id', '=', 'item.DocumentId')
            ->join('Product as product', 'product.Id', '=', 'item.ProductId')
            ->where('document.DocumentTypeId', 2)
            ->whereDate('document.Date', '>=', $startDate->toDateString())
            ->whereDate('document.Date', '<=', $endDate->toDateString())
            ->whereRaw($productCost.' > 0')
            ->selectRaw('DATE(document.Date) as sale_date, COALESCE(SUM(('.$netAmount.') - ('.$productCost.') * item.Quantity), 0) as profit')
            ->groupByRaw('DATE(document.Date)')
            ->orderBy('sale_date')
            ->pluck('profit', 'sale_date')
            ->map(fn ($profit) => (float) $profit);

        if ($selectedProductIds->isNotEmpty()) {
            $baseQuery = DB::table('DocumentItem as item')
                ->join('Document as document', 'document.Id', '=', 'item.DocumentId')
                ->join('Product as product', 'product.Id', '=', 'item.ProductId')
                ->where('document.DocumentTypeId', 2)
                ->whereDate('document.Date', '>=', $startDate->toDateString())
                ->whereDate('document.Date', '<=', $endDate->toDateString())
                ->whereIn('item.ProductId', $selectedProductIds);

            $products = (clone $baseQuery)
                ->selectRaw('product.Id as product_id, product.Name as product_name, COALESCE(SUM(item.Quantity), 0) as total_quantity')
                ->selectRaw('COALESCE(SUM('.$netAmount.'), 0) as total_amount')
                ->selectRaw('COALESCE(SUM(CASE WHEN '.$productCost.' > 0 THEN ('.$netAmount.') - ('.$productCost.') * item.Quantity ELSE 0 END), 0) as total_profit')
                ->selectRaw('COALESCE(SUM(COALESCE(item.Discount, 0) + '.$orderDiscountAllocation.'), 0) as total_discount')
                ->groupBy('product.Id', 'product.Name')
                ->orderBy('product.Name')
                ->get()
                ->map(fn ($product) => (array) $product)
                ->all();

            $dailyQuantities = (clone $baseQuery)
                ->selectRaw('product.Id as product_id, DATE(document.Date) as sale_date, COALESCE(SUM(item.Quantity), 0) as quantity')
                ->groupBy('product.Id')
                ->groupByRaw('DATE(document.Date)')
                ->orderBy('sale_date')
                ->get()
                ->groupBy('product_id')
                ->map(fn ($days) => $days->mapWithKeys(fn ($day) => [$day->sale_date => (float) $day->quantity])->all());
        }

        $trendLabels = [];
        $profitTrendValues = [];

        for ($day = $startDate->copy(); $day->lessThanOrEqualTo($endDate); $day->addDay()) {
            $trendLabels[] = $day->format('M j');
            $profitTrendValues[] = (float) $dailyProfit->get($day->toDateString(), 0);
        }

        $trendDatasets = array_map(function (array $product) use ($dailyQuantities, $startDate, $endDate): array {
            $dailyValues = $dailyQuantities->get($product['Id'], []);
            $values = [];

            for ($day = $startDate->copy(); $day->lessThanOrEqualTo($endDate); $day->addDay()) {
                $values[] = (float) ($dailyValues[$day->toDateString()] ?? 0);
            }

            return [
                'label' => $product['Name'],
                'data' => $values,
            ];
        }, $selectedProducts);

        $rangeProfitTotal = array_sum($profitTrendValues);

        return [
            'productOptions' => [],
            'selectedProducts' => $selectedProducts,
            'products' => $products,
            'trend' => [
                'labels' => $trendLabels,
                'datasets' => $trendDatasets,
            ],
            'profitTrend' => [
                'labels' => $trendLabels,
                'values' => $profitTrendValues,
            ],
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'totalAmount' => array_sum(array_column($products, 'total_amount')),
            'totalProfit' => array_sum(array_column($products, 'total_profit')),
            'profitTotal' => $rangeProfitTotal,
            'totalDiscount' => array_sum(array_column($products, 'total_discount')),
            'totalQuantity' => array_sum(array_column($products, 'total_quantity')),
        ];
    }
}
