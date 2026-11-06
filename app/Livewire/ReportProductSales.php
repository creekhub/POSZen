<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Async;
use Livewire\Component;

class ReportProductSales extends Component
{
    public array $productCategoryIds = [];
    public array $selectedProductIds = [];
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
            'startDate' => $this->productStartDate,
            'endDate' => $this->productEndDate,
            'totalAmount' => 0,
            'totalProfit' => 0,
            'totalDiscount' => 0,
            'totalQuantity' => 0,
        ];
    }

    public function updatedProductCategoryIds(): void
    {
        $this->selectedProductIds = [];
    }

    #[Async]
    public function searchProducts(): void
    {
        $this->validate([
            'productCategoryIds' => ['array', 'max:100'],
            'productCategoryIds.*' => ['integer', 'exists:ProductGroup,Id'],
            'selectedProductIds' => ['array', 'max:100'],
            'selectedProductIds.*' => ['integer', 'exists:Product,Id'],
            'productStartDate' => ['required', 'date'],
            'productEndDate' => ['required', 'date', 'after_or_equal:productStartDate'],
        ]);

        $this->productReport = $this->buildProductReport();

        $this->dispatch(
            'product-report-updated',
            labels: $this->productReport['trend']['labels'],
            datasets: $this->productReport['trend']['datasets'],
        );
    }

    public function render(): View
    {
        $productCategories = DB::table('ProductGroup')->orderBy('Name')->get(['Id', 'Name']);

        return view('livewire.report-product-sales', [
            'productReport' => $this->productReport,
            'productCategories' => $productCategories,
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
        $selectedCategoryIds = $this->selectedCategoryIds();

        if ($selectedProductIds->isEmpty() && $selectedCategoryIds !== []) {
            $selectedProductIds = DB::table('Product')
                ->whereIn('ProductGroupId', $selectedCategoryIds)
                ->orderBy('Name')
                ->pluck('Id')
                ->map(fn ($productId) => (int) $productId)
                ->values();
        }

        $selectedProducts = $selectedProductIds->isNotEmpty()
            ? DB::table('Product')
                ->whereIn('Id', $selectedProductIds)
                ->when($selectedCategoryIds !== [], fn ($query) => $query->whereIn('ProductGroupId', $selectedCategoryIds))
                ->orderBy('Name')
                ->get(['Id', 'Name'])
                ->map(fn ($product) => (array) $product)
                ->all()
            : [];
        $groupByCategory = $selectedCategoryIds !== [];
        $groupIdColumn = $groupByCategory ? 'product_group.Id' : 'product.Id';
        $groupNameColumn = $groupByCategory ? 'product_group.Name' : 'product.Name';
        $trendGroups = $groupByCategory
            ? DB::table('ProductGroup')
                ->whereIn('Id', $selectedCategoryIds)
                ->get(['Id', 'Name'])
                ->map(fn ($category) => (array) $category)
                ->all()
            : $selectedProducts;

        $products = [];
        $dailyQuantities = collect();
        $lineAmount = 'COALESCE(item.TotalAfterDocumentDiscount, item.Total, item.PriceAfterDiscount * item.Quantity)';
        $documentSubtotal = '(SELECT COALESCE(SUM(COALESCE(discount_item.TotalAfterDocumentDiscount, discount_item.Total, discount_item.PriceAfterDiscount * discount_item.Quantity)), 0) FROM DocumentItem as discount_item WHERE discount_item.DocumentId = document.Id)';
        $effectiveOrderDiscount = '(CASE WHEN COALESCE(document.Discount, 0) > '.$documentSubtotal.' THEN '.$documentSubtotal.' ELSE COALESCE(document.Discount, 0) END)';
        $orderDiscountAllocation = '('.$effectiveOrderDiscount.' * '.$lineAmount.' / NULLIF('.$documentSubtotal.', 0))';
        $netAmount = '(CASE WHEN '.$lineAmount.' <= '.$orderDiscountAllocation.' THEN 0 ELSE '.$lineAmount.' - '.$orderDiscountAllocation.' END)';
        $productCost = 'COALESCE(NULLIF(item.ProductCost, 0), NULLIF(product.Cost, 0), product.LastPurchasePrice, 0)';

        if ($selectedProductIds->isNotEmpty()) {
            $baseQuery = DB::table('DocumentItem as item')
                ->join('Document as document', 'document.Id', '=', 'item.DocumentId')
                ->join('Product as product', 'product.Id', '=', 'item.ProductId')
                ->leftJoin('ProductGroup as product_group', 'product_group.Id', '=', 'product.ProductGroupId')
                ->where('document.DocumentTypeId', 2)
                ->whereDate('document.Date', '>=', $startDate->toDateString())
                ->whereDate('document.Date', '<=', $endDate->toDateString())
                ->whereIn('item.ProductId', $selectedProductIds)
                ->when($selectedCategoryIds !== [], fn ($query) => $query->whereIn('product.ProductGroupId', $selectedCategoryIds));

            $products = (clone $baseQuery)
                ->selectRaw($groupIdColumn.' as product_id, '.$groupNameColumn.' as product_name, COALESCE(SUM(item.Quantity), 0) as total_quantity')
                ->selectRaw('COALESCE(SUM('.$netAmount.'), 0) as total_amount')
                ->selectRaw('COALESCE(SUM(CASE WHEN '.$productCost.' > 0 THEN ('.$netAmount.') - ('.$productCost.') * item.Quantity ELSE 0 END), 0) as total_profit')
                ->selectRaw('COALESCE(SUM(COALESCE(item.Discount, 0) + '.$orderDiscountAllocation.'), 0) as total_discount')
                ->groupBy($groupIdColumn, $groupNameColumn)
                ->orderBy($groupNameColumn)
                ->get()
                ->map(fn ($product) => (array) $product)
                ->all();

            $dailyQuantities = (clone $baseQuery)
                ->selectRaw($groupIdColumn.' as product_id, DATE(document.Date) as sale_date, COALESCE(SUM(item.Quantity), 0) as quantity')
                ->groupBy($groupIdColumn)
                ->groupByRaw('DATE(document.Date)')
                ->orderBy('sale_date')
                ->get()
                ->groupBy('product_id')
                ->map(fn ($days) => $days->mapWithKeys(fn ($day) => [$day->sale_date => (float) $day->quantity])->all());
        }

        $trendLabels = [];

        for ($day = $startDate->copy(); $day->lessThanOrEqualTo($endDate); $day->addDay()) {
            $trendLabels[] = $day->format('M j');
        }

        $trendDatasets = array_map(function (array $group) use ($dailyQuantities, $startDate, $endDate): array {
            $dailyValues = $dailyQuantities->get($group['Id'], []);
            $values = [];

            for ($day = $startDate->copy(); $day->lessThanOrEqualTo($endDate); $day->addDay()) {
                $values[] = (float) ($dailyValues[$day->toDateString()] ?? 0);
            }

            return [
                'label' => $group['Name'],
                'data' => $values,
            ];
        }, $trendGroups);

        return [
            'productOptions' => [],
            'selectedProducts' => $selectedProducts,
            'products' => $products,
            'trend' => [
                'labels' => $trendLabels,
                'datasets' => $trendDatasets,
            ],
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'totalAmount' => array_sum(array_column($products, 'total_amount')),
            'totalProfit' => array_sum(array_column($products, 'total_profit')),
            'totalDiscount' => array_sum(array_column($products, 'total_discount')),
            'totalQuantity' => array_sum(array_column($products, 'total_quantity')),
        ];
    }

    private function selectedCategoryIds(): array
    {
        return collect($this->productCategoryIds)
            ->filter(fn ($categoryId) => filter_var($categoryId, FILTER_VALIDATE_INT) !== false && (int) $categoryId > 0)
            ->map(fn ($categoryId) => (int) $categoryId)
            ->unique()
            ->values()
            ->all();
    }
}
