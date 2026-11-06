<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Report extends Component
{
    use WithPagination;

    private const DOCUMENT_TABLE = 'Document as document';

    public string $startDate = '';

    public string $endDate = '';

    public function mount(): void
    {
        $today = Carbon::today();
        $this->startDate = $today->toDateString();
        $this->endDate = $today->toDateString();
    }

    public function applyFilters(): void
    {
        $this->validate([
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
        ]);

        $this->resetPage();
    }

    public function render(): View
    {
        $startDate = Carbon::parse($this->startDate)->startOfDay();
        $endDate = Carbon::parse($this->endDate)->endOfDay();

        $salesQuery = DB::table(self::DOCUMENT_TABLE)
            ->leftJoin('Customer as customer', 'customer.Id', '=', 'document.CustomerId')
            ->where('document.DocumentTypeId', 2)
            ->whereBetween('document.DateCreated', [$startDate, $endDate]);

        $transactionCount = (clone $salesQuery)->count('document.Id');
        $salesTotal = (float) (clone $salesQuery)->sum('document.Total');
        $averageSale = $transactionCount > 0 ? $salesTotal / $transactionCount : 0;

        $sales = (clone $salesQuery)
            ->select([
                'document.Id',
                'document.Number',
                'document.DateCreated',
                'document.Total',
                'customer.Name as customer_name',
            ])
            ->orderByDesc('document.DateCreated')
            ->orderByDesc('document.Id')
            ->paginate(20);

        $documentSubtotals = DB::table('DocumentItem as discount_item')
            ->join(self::DOCUMENT_TABLE, 'document.Id', '=', 'discount_item.DocumentId')
            ->where('document.DocumentTypeId', 2)
            ->whereBetween('document.DateCreated', [$startDate, $endDate])
            ->select('discount_item.DocumentId')
            ->selectRaw('COALESCE(SUM(COALESCE(discount_item.TotalAfterDocumentDiscount, discount_item.Total, discount_item.PriceAfterDiscount * discount_item.Quantity)), 0) as subtotal')
            ->groupBy('discount_item.DocumentId');
        $lineAmount = 'COALESCE(item.TotalAfterDocumentDiscount, item.Total, item.PriceAfterDiscount * item.Quantity)';
        $documentSubtotal = 'COALESCE(document_subtotal.subtotal, 0)';
        $effectiveOrderDiscount = '(CASE WHEN COALESCE(document.Discount, 0) > '.$documentSubtotal.' THEN '.$documentSubtotal.' ELSE COALESCE(document.Discount, 0) END)';
        $orderDiscountAllocation = '('.$effectiveOrderDiscount.' * '.$lineAmount.' / NULLIF('.$documentSubtotal.', 0))';
        $netAmount = '(CASE WHEN '.$lineAmount.' <= '.$orderDiscountAllocation.' THEN 0 ELSE '.$lineAmount.' - '.$orderDiscountAllocation.' END)';
        $productCost = 'COALESCE(NULLIF(item.ProductCost, 0), NULLIF(product.Cost, 0), product.LastPurchasePrice, 0)';
        $lineProfit = '(('.$netAmount.') - ('.$productCost.') * item.Quantity)';

        $productQuery = DB::table('DocumentItem as item')
            ->join(self::DOCUMENT_TABLE, 'document.Id', '=', 'item.DocumentId')
            ->join('Product as product', 'product.Id', '=', 'item.ProductId')
            ->leftJoinSub($documentSubtotals, 'document_subtotal', 'document_subtotal.DocumentId', '=', 'document.Id')
            ->where('document.DocumentTypeId', 2)
            ->whereBetween('document.DateCreated', [$startDate, $endDate]);

        $unitsSold = (float) (clone $productQuery)->sum('item.Quantity');
        $profitTotal = (float) (clone $productQuery)
            ->whereRaw($productCost.' > 0')
            ->selectRaw('COALESCE(SUM('.$lineProfit.'), 0) as profit')
            ->value('profit');
        $topProducts = (clone $productQuery)
            ->select('product.Id', 'product.Name', 'product.Code')
            ->selectRaw('COALESCE(SUM(item.Quantity), 0) as quantity_sold')
            ->selectRaw('COALESCE(SUM(COALESCE(item.TotalAfterDocumentDiscount, item.Total, item.PriceAfterDiscount * item.Quantity)), 0) as sales_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$productCost.' > 0 THEN '.$lineProfit.' ELSE 0 END), 0) as profit')
            ->groupBy('product.Id', 'product.Name', 'product.Code')
            ->orderByDesc('quantity_sold')
            ->limit(10)
            ->get();

        return view('livewire.report', [
            'sales' => $sales,
            'salesTotal' => $salesTotal,
            'profitTotal' => $profitTotal,
            'transactionCount' => $transactionCount,
            'averageSale' => $averageSale,
            'unitsSold' => $unitsSold,
            'topProducts' => $topProducts,
        ]);
    }
}
