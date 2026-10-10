<?php

namespace App\Livewire;

use Illuminate\Support\Collection;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Customers extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $editingCustomerId = null;
    public string $editName = '';
    public string $editCode = '';
    public string $editEmail = '';
    public string $editPhoneNumber = '';
    public string $editAddress = '';
    public string $editCity = '';
    public bool $editIsEnabled = true;
    public ?int $selectedCustomerId = null;
    public ?int $paymentDocumentId = null;
    public int $paymentTypeId = 0;
    public string $paymentAmount = '';
    public string $actionMessage = '';

    public function editCustomer(int $customerId): void
    {
        $customer = DB::table('Customer')->where('Id', $customerId)->where('IsCustomer', 1)->first();

        if (! $customer) {
            $this->addError('customer', 'Customer not found.');
            return;
        }

        $this->resetValidation();
        $this->editingCustomerId = $customerId;
        $this->editName = (string) $customer->Name;
        $this->editCode = (string) ($customer->Code ?? '');
        $this->editEmail = (string) ($customer->Email ?? '');
        $this->editPhoneNumber = (string) ($customer->PhoneNumber ?? '');
        $this->editAddress = (string) ($customer->Address ?? '');
        $this->editCity = (string) ($customer->City ?? '');
        $this->editIsEnabled = (bool) $customer->IsEnabled;
    }

    public function saveCustomer(): void
    {
        if (! $this->editingCustomerId) {
            return;
        }

        $validated = $this->validate([
            'editName' => ['required', 'string', 'max:255'],
            'editCode' => ['nullable', 'string', 'max:255'],
            'editEmail' => ['nullable', 'email', 'max:255'],
            'editPhoneNumber' => ['nullable', 'string', 'max:50'],
            'editAddress' => ['nullable', 'string', 'max:255'],
            'editCity' => ['nullable', 'string', 'max:255'],
            'editIsEnabled' => ['boolean'],
        ]);

        DB::table('Customer')
            ->where('Id', $this->editingCustomerId)
            ->where('IsCustomer', 1)
            ->update([
                'Name' => trim($validated['editName']),
                'Code' => $validated['editCode'] ?: null,
                'Email' => $validated['editEmail'] ?: null,
                'PhoneNumber' => $validated['editPhoneNumber'] ?: null,
                'Address' => $validated['editAddress'] ?: null,
                'City' => $validated['editCity'] ?: null,
                'IsEnabled' => $validated['editIsEnabled'],
                'DateUpdated' => now(),
            ]);

        $this->editingCustomerId = null;
        $this->actionMessage = 'Customer updated.';
    }

    public function viewStatement(int $customerId): void
    {
        $customerExists = DB::table('Customer')->where('Id', $customerId)->where('IsCustomer', 1)->exists();

        if (! $customerExists) {
            $this->addError('customer', 'Customer not found.');
            return;
        }

        $this->resetValidation();
        $this->selectedCustomerId = $customerId;
        $this->paymentDocumentId = null;
        $this->paymentAmount = '';
        $this->actionMessage = '';
    }

    public function closeStatement(): void
    {
        $this->selectedCustomerId = null;
        $this->paymentDocumentId = null;
        $this->paymentAmount = '';
        $this->resetValidation();
    }

    public function startCustomerPayment(int $customerId): void
    {
        $this->viewStatement($customerId);

        if (! $this->selectedCustomerId) {
            return;
        }

        $firstSale = $this->unpaidSalesForCustomers([$customerId])->sortBy('Date')->first();

        if (! $firstSale) {
            $this->actionMessage = 'This customer has no outstanding balance.';
            return;
        }

        $this->startPayment((int) $firstSale->Id);
    }

    public function startPayment(int $documentId): void
    {
        if (! $this->selectedCustomerId) {
            return;
        }

        $sale = $this->unpaidSalesForCustomers([$this->selectedCustomerId])
            ->firstWhere('Id', $documentId);

        if (! $sale || $sale->balance <= 0) {
            $this->addError('paymentAmount', 'This invoice has no outstanding balance.');
            return;
        }

        $paymentType = DB::table('PaymentType')
            ->where('IsEnabled', 1)
            ->where('MarkAsPaid', 1)
            ->orderBy('Ordinal')
            ->orderBy('Name')
            ->value('Id');

        if (! $paymentType) {
            $this->addError('paymentTypeId', 'No active payment methods are available for receiving payment.');
            return;
        }

        $this->resetValidation();
        $this->paymentDocumentId = $documentId;
        $this->paymentTypeId = (int) $paymentType;
        $this->paymentAmount = number_format((float) $sale->balance, 2, '.', '');
    }

    public function cancelPayment(): void
    {
        $this->paymentDocumentId = null;
        $this->paymentAmount = '';
        $this->resetValidation();
    }

    public function recordPayment(): void
    {
        $validated = $this->validate([
            'selectedCustomerId' => ['required', 'integer', 'exists:Customer,Id'],
            'paymentDocumentId' => ['required', 'integer', 'exists:Document,Id'],
            'paymentTypeId' => ['required', 'integer', 'exists:PaymentType,Id'],
            'paymentAmount' => ['required', 'numeric', 'gt:0'],
        ]);

        $paymentType = DB::table('PaymentType')
            ->where('Id', $validated['paymentTypeId'])
            ->where('IsEnabled', 1)
            ->where('MarkAsPaid', 1)
            ->first(['Id']);

        if (! $paymentType) {
            $this->addError('paymentTypeId', 'Select an active payment method.');
            return;
        }

        $amount = round((float) $validated['paymentAmount'], 2);
        $result = DB::transaction(function () use ($validated, $amount): array {
            $sale = DB::table('Document')
                ->where('Id', $validated['paymentDocumentId'])
                ->where('CustomerId', $validated['selectedCustomerId'])
                ->where('DocumentTypeId', 2)
                ->where('PaidStatus', 1)
                ->lockForUpdate()
                ->first(['Id', 'Total']);

            if (! $sale) {
                return ['error' => 'This invoice is no longer awaiting payment. Refresh the statement and try again.'];
            }

            $paidAmount = (float) DB::table('Payment as payment')
                ->join('PaymentType as payment_type', 'payment_type.Id', '=', 'payment.PaymentTypeId')
                ->where('payment.DocumentId', $sale->Id)
                ->where('payment_type.MarkAsPaid', 1)
                ->whereRaw('LOWER(payment_type.Name) <> ?', ['credit'])
                ->sum('payment.Amount');
            $balance = max(0, (float) $sale->Total - $paidAmount);

            if ($amount > $balance + 0.001) {
                return ['error' => 'Payment cannot exceed the outstanding balance of ₱'.number_format($balance, 2).'.'];
            }

            $now = now();
            DB::table('Payment')->insert([
                'DocumentId' => $sale->Id,
                'PaymentTypeId' => $validated['paymentTypeId'],
                'Amount' => $amount,
                'Date' => $now->toDateString(),
                'UserId' => auth()->id(),
                'DateCreated' => $now,
                'RoundingAdjustment' => 0,
            ]);

            if ($amount >= $balance - 0.001) {
                DB::table('Document')->where('Id', $sale->Id)->update([
                    'PaidStatus' => 2,
                    'DateUpdated' => $now,
                ]);
            }

            return ['success' => true];
        });

        if (isset($result['error'])) {
            $this->addError('paymentAmount', $result['error']);
            return;
        }

        $this->paymentDocumentId = null;
        $this->paymentAmount = '';
        $this->actionMessage = 'Payment recorded.';
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $search = trim($this->search);

        $customers = DB::table('Customer')
            ->where('IsCustomer', 1)
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%'.$search.'%';
                $query->where(function ($query) use ($term): void {
                    $query->where('Name', 'like', $term)
                        ->orWhere('Code', 'like', $term)
                        ->orWhere('Email', 'like', $term)
                        ->orWhere('PhoneNumber', 'like', $term);
                });
            })
            ->orderBy('Name')
            ->paginate(20);

        $pageCustomerIds = $customers->getCollection()->pluck('Id')->map(fn ($id) => (int) $id)->all();
        $balancesByCustomer = $this->unpaidSalesForCustomers($pageCustomerIds)
            ->groupBy('CustomerId')
            ->map(fn (Collection $sales): float => (float) $sales->sum('balance'));

        $customers->getCollection()->each(function ($customer) use ($balancesByCustomer): void {
            $customer->outstanding_balance = $balancesByCustomer->get($customer->Id, 0.0);
        });

        $soaCustomer = $this->selectedCustomerId
            ? DB::table('Customer')->where('Id', $this->selectedCustomerId)->where('IsCustomer', 1)->first(['Id', 'Name'])
            : null;
        $unpaidSales = $this->selectedCustomerId
            ? $this->unpaidSalesForCustomers([$this->selectedCustomerId], true)
            : collect();
        $paymentTypes = DB::table('PaymentType')
            ->where('IsEnabled', 1)
            ->where('MarkAsPaid', 1)
            ->orderBy('Ordinal')
            ->orderBy('Name')
            ->get(['Id', 'Name']);

        return view('livewire.customers', compact('customers', 'soaCustomer', 'unpaidSales', 'paymentTypes'));
    }

    private function unpaidSalesForCustomers(array $customerIds, bool $includeItems = false): Collection
    {
        if ($customerIds === []) {
            return collect();
        }

        $sales = DB::table('Document as document')
            ->leftJoin('Payment as payment', 'payment.DocumentId', '=', 'document.Id')
            ->leftJoin('PaymentType as payment_type', 'payment_type.Id', '=', 'payment.PaymentTypeId')
            ->where('document.DocumentTypeId', 2)
            ->where('document.PaidStatus', 1)
            ->whereIn('document.CustomerId', $customerIds)
            ->select([
                'document.Id',
                'document.CustomerId',
                'document.Number',
                'document.Date',
                'document.Total',
            ])
            ->selectRaw('COALESCE(SUM(CASE WHEN payment_type.MarkAsPaid = 1 AND LOWER(payment_type.Name) <> "credit" THEN payment.Amount ELSE 0 END), 0) as paid_amount')
            ->groupBy('document.Id', 'document.CustomerId', 'document.Number', 'document.Date', 'document.Total')
            ->orderByDesc('document.Date')
            ->orderByDesc('document.Id')
            ->get();

        $sales->each(function ($sale): void {
            $sale->balance = max(0, (float) $sale->Total - (float) $sale->paid_amount);
            $sale->items = collect();
        });

        if ($includeItems && $sales->isNotEmpty()) {
            $itemsByDocument = DB::table('DocumentItem as item')
                ->join('Product as product', 'product.Id', '=', 'item.ProductId')
                ->whereIn('item.DocumentId', $sales->pluck('Id'))
                ->select([
                    'item.DocumentId',
                    'product.Name as product_name',
                    'item.Quantity',
                    'item.Price',
                    'item.Discount',
                    'item.Total',
                ])
                ->orderBy('product.Name')
                ->get()
                ->groupBy('DocumentId');

            $sales->each(function ($sale) use ($itemsByDocument): void {
                $sale->items = $itemsByDocument->get($sale->Id, collect());
            });
        }

        return $sales;
    }
}
