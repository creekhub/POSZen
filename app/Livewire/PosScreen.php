<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PosScreen extends Component
{
    public string $search = '';
    public int $selectedCustomerId = 1;
    public bool $splitPayment = false;
    public int $selectedPaymentTypeId = 1;
    public string $tenderedAmount = '';
    public bool $tenderedAmountEdited = false;
    public array $paymentAllocations = [];
    public float $orderDiscount = 0;
    public array $cart = [];
    public string $message = '';
    public string $error = '';

    public function updatedSearch(string $value): void
    {
        $this->scanBarcode($value);
    }

    public function scanBarcode(?string $value = null): void
    {
        $barcode = trim($value ?? $this->search);

        if ($barcode === '') {
            return;
        }

        $productId = DB::table('Barcode')
            ->where('Value', $barcode)
            ->value('ProductId');

        if (! $productId) {
            return;
        }

        $this->addToCart((int) $productId, true);
        $this->search = '';
    }

    public function getItemsProperty(): array
    {
        $query = DB::table('Product as product')
            ->leftJoin('Stock as stock', function ($join) {
                $join->on('stock.ProductId', '=', 'product.Id')
                    ->where('stock.WarehouseId', 1);
            })
            ->where('product.IsEnabled', 1)
            ->where(function ($query) {
                $query->where('product.IsService', 1)
                    ->orWhere('stock.Quantity', '>', 0);
            });

        if ($this->search !== '') {
            $search = '%'.$this->search.'%';

            $query->orWhere(function ($query) use ($search) {
                $query->where('product.IsEnabled', 1)
                    ->where(function ($query) use ($search) {
                        $query->where('product.Name', 'like', $search)
                            ->orWhere('product.Code', 'like', $search)
                            ->orWhereExists(function ($barcodeQuery) use ($search) {
                                $barcodeQuery->select(DB::raw(1))
                                    ->from('Barcode as barcode')
                                    ->whereColumn('barcode.ProductId', 'product.Id')
                                    ->where('barcode.Value', 'like', $search);
                            });
                    });
            });
        }

        return $query
            ->select([
                'product.Id as id',
                'product.Name as name',
                'product.Code as code',
                'product.Price as price',
                'product.IsService as is_service',
                DB::raw('COALESCE(stock.Quantity, 0) as stock'),
            ])
            ->orderBy('product.Name')
            ->limit(200)
            ->get()
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'name' => $item->name,
                'code' => $item->code,
                'price' => (float) $item->price,
                'stock' => (float) $item->stock,
                'is_service' => (bool) $item->is_service,
            ])
            ->all();
    }

    public function getPaymentTypesProperty(): array
    {
        return DB::table('PaymentType')
            ->where('IsEnabled', 1)
            ->orderBy('Ordinal')
            ->orderBy('Name')
            ->get(['Id', 'Name', 'IsCustomerRequired', 'MarkAsPaid'])
            ->map(fn ($paymentType) => [
                'id' => (int) $paymentType->Id,
                'name' => $paymentType->Name,
                'is_customer_required' => (bool) $paymentType->IsCustomerRequired,
                'mark_as_paid' => (bool) $paymentType->MarkAsPaid,
            ])
            ->all();
    }

    public function getCustomersProperty(): array
    {
        return DB::table('Customer')
            ->where('IsEnabled', 1)
            ->where('IsCustomer', 1)
            ->orderBy('Name')
            ->get(['Id', 'Name'])
            ->map(fn ($customer) => ['id' => (int) $customer->Id, 'name' => $customer->Name])
            ->all();
    }

    public function selectPaymentType(int $paymentTypeId): void
    {
        $this->selectedPaymentTypeId = $paymentTypeId;
        $this->splitPayment = false;
        $this->tenderedAmount = '';
        $this->tenderedAmountEdited = false;
        $this->paymentAllocations = [];

        $paymentType = collect($this->paymentTypes)->firstWhere('id', $paymentTypeId);

        if (strtolower((string) ($paymentType['name'] ?? '')) === 'cash') {
            $this->setDefaultTenderedAmount();
        }
    }

    public function enableSplitPayment(): void
    {
        $this->splitPayment = true;
        $this->tenderedAmount = '';
        $this->tenderedAmountEdited = false;
        $this->paymentAllocations = [[
            'payment_type_id' => $this->selectedPaymentTypeId,
            'amount' => $this->total,
        ]];
    }

    public function addSplitPayment(int $paymentTypeId): void
    {
        if (collect($this->paymentTypes)->firstWhere('id', $paymentTypeId)) {
            $this->splitPayment = true;
            $allocated = collect($this->paymentAllocations)->sum(fn ($payment) => (float) $payment['amount']);
            $remaining = max(0, round($this->total - $allocated, 2));

            $this->paymentAllocations[] = [
                'payment_type_id' => $paymentTypeId,
                'amount' => $remaining,
            ];
        }
    }

    public function removeSplitPayment(int $index): void
    {
        unset($this->paymentAllocations[$index]);
        $this->paymentAllocations = array_values($this->paymentAllocations);
    }

    public function updatedPaymentAllocations($value, string $key): void
    {
        if (! str_ends_with($key, '.amount') || count($this->paymentAllocations) < 2) {
            return;
        }

        $parts = explode('.', $key);
        $changedIndex = (int) ($parts[0] ?? -1);
        $lastIndex = count($this->paymentAllocations) - 1;

        if ($changedIndex === $lastIndex) {
            return;
        }

        $allocated = collect($this->paymentAllocations)
            ->except($lastIndex)
            ->sum(fn ($payment) => (float) $payment['amount']);

        $this->paymentAllocations[$lastIndex]['amount'] = max(0, round($this->total - $allocated, 2));
    }

    public function setItemDiscount(int $id, $discount): void
    {
        if (isset($this->cart[$id])) {
            $this->cart[$id]['discount'] = $this->normalizeDiscount($discount);
            $this->setDefaultTenderedAmount();
        }
    }

    public function addToCart(int $id, bool $allowUnavailable = false): void
    {
        $item = collect($this->items)->firstWhere('id', $id);

        if (! $item) {
            return;
        }

        $key = $item['id'];

        if (isset($this->cart[$key])) {
            $this->cart[$key]['quantity']++;
            $this->setDefaultTenderedAmount();

            return;
        }

        $this->cart[$key] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'price' => $item['price'],
            'is_service' => $item['is_service'],
            'quantity' => 1,
            'discount' => 0,
        ];
        $this->setDefaultTenderedAmount();
    }

    public function increment(int $id): void
    {
        if (isset($this->cart[$id])) {
            $this->cart[$id]['quantity']++;
            $this->setDefaultTenderedAmount();
        }
    }

    public function decrement(int $id): void
    {
        if (! isset($this->cart[$id])) {
            return;
        }

        if ($this->cart[$id]['quantity'] > 1) {
            $this->cart[$id]['quantity']--;
            $this->setDefaultTenderedAmount();
            return;
        }

        unset($this->cart[$id]);
        $this->setDefaultTenderedAmount();
    }

    public function updatedOrderDiscount(): void
    {
        $this->setDefaultTenderedAmount();
    }

    public function updatedTenderedAmount(): void
    {
        $this->tenderedAmountEdited = true;
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->orderDiscount = 0;
        $this->tenderedAmount = '';
        $this->tenderedAmountEdited = false;
        $this->paymentAllocations = [];
        $this->splitPayment = false;
        $this->error = '';
        $this->message = '';
    }

    public function getFilteredItemsProperty(): array
    {
        return $this->items;
    }

    private function setDefaultTenderedAmount(): void
    {
        if (! $this->tenderedAmountEdited && $this->total > 0) {
            $this->tenderedAmount = number_format($this->total, 2, '.', '');
        }
    }

    public function getTenderedAmountValueProperty(): float
    {
        return $this->parseAmount($this->tenderedAmount) ?? 0;
    }

    private function parseAmount(string $value): ?float
    {
        $value = str_replace(' ', '', trim($value));

        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace(',', '', $value);
        } elseif (str_contains($value, ',')) {
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? round((float) $value, 2) : null;
    }

    private function normalizeDiscount($discount): float
    {
        $discountValue = is_scalar($discount)
            ? ($this->parseAmount((string) $discount) ?? 0)
            : 0;

        return max(0, min(100, $discountValue));
    }

    private function normalizeOrderDiscount($discount): float
    {
        $discountValue = is_scalar($discount)
            ? ($this->parseAmount((string) $discount) ?? 0)
            : 0;

        return max(0, $discountValue);
    }

    public function processPayment(): void
    {
        $this->error = '';
        $this->message = '';

        if ($this->cart === []) {
            $this->error = 'Add at least one item before processing payment.';
            return;
        }

        $saleTotal = $this->total;
        $paymentTypes = collect($this->paymentTypes)->keyBy('id');
        $payments = $this->splitPayment
            ? collect($this->paymentAllocations)
            : collect([['payment_type_id' => $this->selectedPaymentTypeId, 'amount' => $saleTotal]]);

        $payments = $payments->map(function ($payment) use ($paymentTypes) {
            $payment['payment_type_id'] = (int) $payment['payment_type_id'];
            $payment['amount'] = round((float) $payment['amount'], 2);
            $payment['type'] = $paymentTypes->get($payment['payment_type_id']);
            return $payment;
        });

        if ($payments->isEmpty() || $payments->contains(fn ($payment) => ! $payment['type'] || $payment['amount'] <= 0)) {
            $this->error = 'Select an active payment method.';
            $this->tenderedAmountEdited = false;
            return;
        }

        if (abs($payments->sum('amount') - $saleTotal) > 0.01) {
            $this->error = 'Payment amounts must equal the sale total.';
            return;
        }

        $isCashPayment = $payments->count() === 1
            && ! $this->splitPayment
            && strtolower((string) $payments->first()['type']['name']) === 'cash';

        if ($isCashPayment) {
            $tenderedAmount = $this->parseAmount($this->tenderedAmount) ?? 0;

            if ($tenderedAmount <= 0) {
                $this->error = 'Enter the amount tendered.';
                return;
            }

            if ($tenderedAmount < $saleTotal) {
                $this->error = 'The tendered amount must be at least the sale total.';
                return;
            }
        }

        $requiresCustomer = $payments->contains(fn ($payment) => $payment['type']['is_customer_required']);

        if ($requiresCustomer && $this->selectedCustomerId === 1) {
            $this->error = 'Credit payment requires a customer other than Walk-in customer.';
            return;
        }

        $receipt = DB::transaction(function () use ($payments): array {
            $now = now();
            $nextDocumentId = (int) DB::table('Document')->max('Id') + 1;
            $total = $this->total;
            $number = $now->format('y').'-200-'.str_pad((string) $nextDocumentId, 6, '0', STR_PAD_LEFT);

            $documentId = DB::table('Document')->insertGetId([
                'Number' => $number,
                'UserId' => Auth::id(),
                'CustomerId' => $this->selectedCustomerId ?: 1,
                'Date' => $now->toDateString(),
                'StockDate' => $now,
                'Total' => $total,
                'IsClockedOut' => 1,
                'DocumentTypeId' => 2,
                'WarehouseId' => 1,
                'DateCreated' => $now,
                'DateUpdated' => $now,
                'Discount' => $this->normalizeOrderDiscount($this->orderDiscount),
                'DiscountType' => 0,
                'PaidStatus' => $payments->contains(fn ($payment) => ! $payment['type']['mark_as_paid']) ? 1 : 2,
                'DiscountApplyRule' => 0,
                'ServiceType' => 0,
            ]);

            foreach ($this->cart as $item) {
                $grossTotal = round($item['price'] * $item['quantity'], 2);
                $discount = $this->normalizeDiscount($item['discount'] ?? 0);
                $lineDiscount = round($grossTotal * ($discount / 100), 2);
                $lineTotal = round($grossTotal - $lineDiscount, 2);

                DB::table('DocumentItem')->insert([
                    'DocumentId' => $documentId,
                    'ProductId' => $item['id'],
                    'Quantity' => $item['quantity'],
                    'ExpectedQuantity' => $item['quantity'],
                    'PriceBeforeTax' => $item['price'],
                    'Price' => $item['price'],
                    'Discount' => $lineDiscount,
                    'DiscountType' => 0,
                    'ProductCost' => 0,
                    'PriceBeforeTaxAfterDiscount' => round($item['price'] * (1 - $discount / 100), 2),
                    'PriceAfterDiscount' => round($item['price'] * (1 - $discount / 100), 2),
                    'Total' => $lineTotal,
                    'TotalAfterDocumentDiscount' => $lineTotal,
                    'DiscountApplyRule' => 0,
                ]);

                if (! $item['is_service']) {
                    $stock = DB::table('Stock')
                        ->where('ProductId', $item['id'])
                        ->where('WarehouseId', 1)
                        ->lockForUpdate()
                        ->first();

                    if ($stock) {
                        DB::table('Stock')->where('Id', $stock->Id)->decrement('Quantity', $item['quantity']);
                    }
                }
            }

            foreach ($payments as $payment) {
                DB::table('Payment')->insert([
                    'DocumentId' => $documentId,
                    'PaymentTypeId' => $payment['payment_type_id'],
                    'Amount' => $payment['amount'],
                    'Date' => $now->toDateString(),
                    'UserId' => Auth::id(),
                    'DateCreated' => $now,
                    'RoundingAdjustment' => 0,
                ]);
            }

            return [
                'id' => $documentId,
                'number' => $number,
                'date' => $now->format('Y-m-d H:i'),
            ];
        });

        $receipt['subtotal'] = $this->subtotal;
        $receipt['discount'] = $this->normalizeOrderDiscount($this->orderDiscount);
        $receipt['total'] = $this->total;
        $receipt['items'] = collect($this->cart)->map(function ($item): array {
            $discount = $this->normalizeDiscount($item['discount'] ?? 0);
            $grossTotal = round($item['price'] * $item['quantity'], 2);

            return [
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'total' => round($grossTotal - ($grossTotal * $discount / 100), 2),
            ];
        })->values()->all();
        $receipt['payments'] = $payments->map(fn ($payment): array => [
            'name' => $payment['type']['name'],
            'amount' => $payment['amount'],
        ])->values()->all();
        $receipt['tendered'] = $isCashPayment ? $tenderedAmount : 0;
        $receipt['change'] = $isCashPayment ? max(0, round($tenderedAmount - $saleTotal, 2)) : 0;

        $this->dispatch('pos-sale-completed', receipt: $receipt);

        $this->cart = [];
        $this->orderDiscount = 0;
        $this->tenderedAmount = '';
        $this->paymentAllocations = [];
        $this->splitPayment = false;
        $this->message = 'Payment completed successfully.';
    }

    public function getSubtotalProperty(): float
    {
        $total = 0;

        foreach ($this->cart as $item) {
            $discount = $this->normalizeDiscount($item['discount'] ?? 0);
            $total += $item['price'] * $item['quantity'] * (1 - $discount / 100);
        }

        return round($total, 2);
    }

    public function getTotalProperty(): float
    {
        $orderDiscount = $this->normalizeOrderDiscount($this->orderDiscount);

        return max(0, round($this->subtotal - $orderDiscount, 2));
    }

    public function render()
    {
        return view('livewire.pos-screen');
    }
}
