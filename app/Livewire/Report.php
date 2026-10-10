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
        $this->startDate = $today->copy()->startOfMonth()->toDateString();
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

        $productQuery = DB::table('DocumentItem as item')
            ->join(self::DOCUMENT_TABLE, 'document.Id', '=', 'item.DocumentId')
            ->join('Product as product', 'product.Id', '=', 'item.ProductId')
            ->where('document.DocumentTypeId', 2)
            ->whereBetween('document.DateCreated', [$startDate, $endDate]);

        $unitsSold = (float) (clone $productQuery)->sum('item.Quantity');
        $topProducts = (clone $productQuery)
            ->select('product.Id', 'product.Name', 'product.Code')
            ->selectRaw('COALESCE(SUM(item.Quantity), 0) as quantity_sold')
            ->selectRaw('COALESCE(SUM(COALESCE(item.TotalAfterDocumentDiscount, item.Total, item.PriceAfterDiscount * item.Quantity)), 0) as sales_total')
            ->selectRaw('COALESCE(SUM(
                case
                    when item.ProductCost is not null then
                        (item.PriceAfterDiscount - item.ProductCost) * item.Quantity
                    else
                        0
                end
            ), 0) as profit')
            ->groupBy('product.Id', 'product.Name', 'product.Code')
            ->orderByDesc('quantity_sold')
            ->limit(10)
            ->get();

        return view('livewire.report', [
            'sales' => $sales,
            'salesTotal' => $salesTotal,
            'transactionCount' => $transactionCount,
            'averageSale' => $averageSale,
            'unitsSold' => $unitsSold,
            'topProducts' => $topProducts,
        ]);
    }
}

