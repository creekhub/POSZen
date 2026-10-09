<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReportStateOfAccount extends Component
{
    public ?int $accountCustomerId = null;
    public string $accountCustomerSearch = '';

    public function mount(): void
    {
        $this->accountCustomerId = DB::table('Document')
            ->where('DocumentTypeId', 2)
            ->where('PaidStatus', 1)
            ->whereNotNull('CustomerId')
            ->orderBy('CustomerId')
            ->value('CustomerId');

        $this->accountCustomerSearch = (string) DB::table('Customer')
            ->where('Id', $this->accountCustomerId)
            ->value('Name');
    }

    public function updatedAccountCustomerSearch(string $value): void
    {
        $customerId = DB::table('Customer')
            ->where('Name', trim($value))
            ->where('IsEnabled', 1)
            ->where('IsCustomer', 1)
            ->value('Id');

        $this->accountCustomerId = $customerId ? (int) $customerId : null;
    }

    public function render(): View
    {
        $accountCustomers = DB::table('Customer as customer')
            ->join('Document as document', 'document.CustomerId', '=', 'customer.Id')
            ->where('document.DocumentTypeId', 2)
            ->where('document.PaidStatus', 1)
            ->where('customer.IsEnabled', 1)
            ->where('customer.IsCustomer', 1)
            ->select('customer.Id', 'customer.Name')
            ->distinct()
            ->orderBy('customer.Name')
            ->get();

        $unpaidSales = DB::table('Document as document')
            ->leftJoin('Payment as payment', 'payment.DocumentId', '=', 'document.Id')
            ->leftJoin('PaymentType as payment_type', 'payment_type.Id', '=', 'payment.PaymentTypeId')
            ->where('document.DocumentTypeId', 2)
            ->where('document.PaidStatus', 1)
            ->where('document.CustomerId', $this->accountCustomerId)
            ->select([
                'document.Id',
                'document.Number',
                'document.Date',
                'document.DueDate',
                'document.Total',
                'document.PaidStatus',
            ])
            ->selectRaw('COALESCE(SUM(CASE WHEN payment_type.MarkAsPaid = 1 AND LOWER(payment_type.Name) <> "credit" THEN payment.Amount ELSE 0 END), 0) as paid_amount')
            ->groupBy('document.Id', 'document.Number', 'document.Date', 'document.DueDate', 'document.Total', 'document.PaidStatus')
            ->orderByDesc('document.Date')
            ->orderByDesc('document.Id')
            ->get();

        $saleItems = DB::table('DocumentItem as item')
            ->join('Product as product', 'product.Id', '=', 'item.ProductId')
            ->whereIn('item.DocumentId', $unpaidSales->pluck('Id'))
            ->select([
                'item.DocumentId',
                'item.ProductId',
                'product.Name as product_name',
                'item.Quantity',
                'item.Price',
                'item.Discount',
                'item.Total',
            ])
            ->orderBy('product.Name')
            ->get()
            ->groupBy('DocumentId');

        $unpaidSales->each(function ($sale) use ($saleItems): void {
            $sale->balance = max(0, (float) $sale->Total - (float) $sale->paid_amount);
            $sale->items = $saleItems->get($sale->Id, collect());
        });

        $accountCustomer = $accountCustomers->firstWhere('Id', $this->accountCustomerId);

        return view('livewire.report-state-of-account', [
            'accountCustomers' => $accountCustomers,
            'accountCustomer' => $accountCustomer,
            'unpaidSales' => $unpaidSales,
            'accountGrandTotal' => $unpaidSales->sum('Total'),
            'accountTotalPayment' => $unpaidSales->sum('paid_amount'),
            'accountBalance' => $unpaidSales->sum('balance'),
        ]);
    }
}
