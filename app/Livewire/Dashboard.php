<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public ?int $accountCustomerId = null;
    public string $accountCustomerSearch = '';
    public string $chartPeriod = 'Weekly';
    public string $chartStartDate = '';
    public string $chartEndDate = '';
    public string $taskTitle = '';
    public string $taskDescription = '';
    public string $taskDueDate = '';
    public ?int $taskCustomerId = null;
    public ?int $taskDocumentId = null;
    public string $taskCustomerSearch = '';
    public string $taskDocumentSearch = '';
    public string $taskMessage = '';
    public string $taskError = '';
    public bool $showTaskReminder = true;

    public function mount(): void
    {
        $today = Carbon::today();
        $this->chartStartDate = $today->copy()->subDays(6)->toDateString();
        $this->chartEndDate = $today->toDateString();
        $this->taskDueDate = $today->toDateString();

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

    public function updatedChartPeriod(string $period): void
    {
        $today = Carbon::today();

        [$startDate, $endDate] = match ($period) {
            'Daily' => [$today, $today],
            'Monthly' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
            'Yearly' => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
            default => [$today->copy()->subDays(6), $today],
        };

        $this->chartStartDate = $startDate->toDateString();
        $this->chartEndDate = $endDate->toDateString();
    }

    public function updatedAccountCustomerSearch(string $value): void
    {
        $customerId = DB::table('Customer')
            ->where('Name', $value)
            ->where('IsEnabled', 1)
            ->where('IsCustomer', 1)
            ->value('Id');

        if ($customerId) {
            $this->accountCustomerId = (int) $customerId;
        }
    }

    public function updatedTaskCustomerSearch(string $value): void
    {
        $this->taskCustomerId = DB::table('Customer')
            ->where('Name', trim($value))
            ->where('IsEnabled', 1)
            ->where('IsCustomer', 1)
            ->value('Id');

        $this->taskCustomerId = $this->taskCustomerId ? (int) $this->taskCustomerId : null;
    }

    public function updatedTaskDocumentSearch(string $value): void
    {
        $this->taskDocumentId = DB::table('Document')
            ->where('Number', trim($value))
            ->where('DocumentTypeId', 2)
            ->value('Id');

        $this->taskDocumentId = $this->taskDocumentId ? (int) $this->taskDocumentId : null;
    }

    public function createTask(): void
    {
        $this->taskMessage = '';
        $this->taskError = '';

        $validated = $this->validate([
            'taskTitle' => ['required', 'string', 'max:255'],
            'taskDescription' => ['nullable', 'string', 'max:5000'],
            'taskDueDate' => ['required', 'date'],
            'taskCustomerId' => ['nullable', 'integer', 'exists:Customer,Id'],
            'taskDocumentId' => ['nullable', 'integer', 'exists:Document,Id'],
        ]);

        DB::table('Task')->insert([
            'Title' => $validated['taskTitle'],
            'Description' => $validated['taskDescription'] ?: null,
            'DueDate' => $validated['taskDueDate'],
            'IsCompleted' => false,
            'UserId' => Auth::id(),
            'CustomerId' => $validated['taskCustomerId'],
            'DocumentId' => $validated['taskDocumentId'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->reset([
            'taskTitle',
            'taskDescription',
            'taskCustomerId',
            'taskDocumentId',
            'taskCustomerSearch',
            'taskDocumentSearch',
        ]);
        $this->taskDueDate = Carbon::today()->toDateString();
        $this->taskMessage = 'Task created.';
    }

    public function completeTask(int $taskId): void
    {
        DB::table('Task')
            ->where('Id', $taskId)
            ->where('UserId', Auth::id())
            ->where('IsCompleted', false)
            ->update(['IsCompleted' => true, 'updated_at' => now()]);

        $this->showTaskReminder = false;
    }

    public function dismissTaskReminder(): void
    {
        $this->showTaskReminder = false;
    }

    public function render()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $sales = DB::table('Document')
            ->where('DocumentTypeId', 2);

        $todaySales = (clone $sales)
            ->whereDate('Date', $today)
            ->sum('Total');

        $todayOrderDiscount = (clone $sales)
            ->whereDate('Date', $today)
            ->sum('Discount');

        $todayItemDiscount = DB::table('DocumentItem as item')
            ->join('Document as document', 'document.Id', '=', 'item.DocumentId')
            ->where('document.DocumentTypeId', 2)
            ->whereDate('document.Date', $today)
            ->sum('item.Discount');

        $todayDiscount = (float) $todayOrderDiscount + (float) $todayItemDiscount;

        $salesChart = $this->buildSalesChart();

        $receivableDocuments = DB::table('Document as document')
            ->leftJoin('Payment as payment', 'payment.DocumentId', '=', 'document.Id')
            ->leftJoin('PaymentType as payment_type', 'payment_type.Id', '=', 'payment.PaymentTypeId')
            ->where('document.DocumentTypeId', 2)
            ->where('document.PaidStatus', 1)
            ->selectRaw('document.Id, document.Total, COALESCE(SUM(CASE WHEN payment_type.MarkAsPaid = 1 AND LOWER(payment_type.Name) <> "credit" THEN payment.Amount ELSE 0 END), 0) as paid_amount')
            ->groupBy('document.Id', 'document.Total')
            ->get();

        $receivableTotal = $receivableDocuments->sum(fn ($document) => max(0, (float) $document->Total - (float) $document->paid_amount));

        $todayReceivableDocuments = DB::table('Document as document')
            ->leftJoin('Payment as payment', 'payment.DocumentId', '=', 'document.Id')
            ->leftJoin('PaymentType as payment_type', 'payment_type.Id', '=', 'payment.PaymentTypeId')
            ->where('document.DocumentTypeId', 2)
            ->where('document.PaidStatus', 1)
            ->whereDate('document.Date', $today)
            ->selectRaw('document.Id, document.Total, COALESCE(SUM(CASE WHEN payment_type.MarkAsPaid = 1 AND LOWER(payment_type.Name) <> "credit" THEN payment.Amount ELSE 0 END), 0) as paid_amount')
            ->groupBy('document.Id', 'document.Total')
            ->get();

        $todayReceivableTotal = $todayReceivableDocuments->sum(fn ($document) => max(0, (float) $document->Total - (float) $document->paid_amount));

        $todayProfit = (float) DB::table('DocumentItem as item')
            ->join('Document as document', 'document.Id', '=', 'item.DocumentId')
            ->where('document.DocumentTypeId', 2)
            ->whereDate('document.Date', $today)
            ->whereNotNull('item.ProductCost')
            ->where('item.ProductCost', '>', 0)
            ->selectRaw('COALESCE(SUM((item.PriceAfterDiscount - item.ProductCost) * item.Quantity), 0) as profit')
            ->value('profit');

        $startingCashEntries = collect();
        $startingCashToday = 0.0;

        if (DB::getSchemaBuilder()->hasTable('StartingCash')) {
            $startingCashEntries = DB::table('StartingCash')
                ->whereDate('DateCreated', $today)
                ->orderByDesc('DateCreated')
                ->get()
                ->map(function ($entry) {
                    $entry->type_label = match ((int) ($entry->StartingCashType ?? 0)) {
                        0 => 'Cash In',
                        1 => 'Cash Out',
                        default => 'Other',
                    };
                    $entry->description = trim((string) ($entry->Description ?? $entry->description ?? '')) ?: 'No description';
                    $entry->amount_value = (float) ($entry->Amount ?? 0);
                    return $entry;
                });

            $startingCashToday = (float) $startingCashEntries->sum(fn ($entry) => $entry->StartingCashType == 0 ? $entry->amount_value : -$entry->amount_value);
        }

        $stats = [
            ['label' => 'Sales Today', 'value' => '₱'.number_format($todaySales, 2), 'trend' => $today->format('M j')],
            ['label' => 'Discount Today', 'value' => '₱'.number_format($todayDiscount, 2), 'trend' => $today->format('M j')],
            ['label' => 'Receivables', 'value' => '₱'.number_format($receivableTotal, 2), 'trend' => 'Total outstanding'],
            ['label' => "Today's Receivables", 'value' => '₱'.number_format($todayReceivableTotal, 2), 'trend' => $today->format('M j')],
            ['label' => "Today's Profit", 'value' => '₱'.number_format($todayProfit, 2), 'trend' => $today->format('M j')],
            ['label' => 'Starting Cash', 'value' => '₱'.number_format($startingCashToday, 2), 'trend' => $today->format('M j')],
        ];

        $recentTransactions = DB::table('Document as document')
            ->leftJoin('Customer as customer', 'customer.Id', '=', 'document.CustomerId')
            ->leftJoin('Payment as payment', 'payment.DocumentId', '=', 'document.Id')
            ->leftJoin('PaymentType as payment_type', 'payment_type.Id', '=', 'payment.PaymentTypeId')
            ->where('document.DocumentTypeId', 2)
            ->select([
                'document.Id',
                'document.Number',
                'document.Date',
                'document.Total',
                'customer.Name as customer_name',
                'payment_type.Name as payment_type',
            ])
            ->orderByDesc('document.Id')
            ->limit(5)
            ->get();

        $recentTransactionItems = DB::table('DocumentItem as item')
            ->join('Product as product', 'product.Id', '=', 'item.ProductId')
            ->whereIn('item.DocumentId', $recentTransactions->pluck('Id'))
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

        $recentTransactions->each(function ($transaction) use ($recentTransactionItems): void {
            $transaction->items = $recentTransactionItems->get($transaction->Id, collect());
        });

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
                DB::raw('COALESCE(SUM(CASE WHEN payment_type.MarkAsPaid = 1 AND LOWER(payment_type.Name) <> "credit" THEN payment.Amount ELSE 0 END), 0) as paid_amount'),
            ])
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
        $accountGrandTotal = $unpaidSales->sum('Total');
        $accountTotalPayment = $unpaidSales->sum('paid_amount');
        $accountBalance = $unpaidSales->sum('balance');

        $taskCustomers = DB::table('Customer')
            ->where('IsEnabled', 1)
            ->where('IsCustomer', 1)
            ->orderBy('Name')
            ->get(['Id', 'Name']);

        $taskDocuments = DB::table('Document as document')
            ->leftJoin('Customer as customer', 'customer.Id', '=', 'document.CustomerId')
            ->where('document.DocumentTypeId', 2)
            ->orderByDesc('document.Date')
            ->orderByDesc('document.Id')
            ->limit(100)
            ->get(['document.Id', 'document.Number', 'document.Date', 'customer.Name as customer_name']);

        $taskProducts = DB::table('Product')
            ->where('IsEnabled', 1)
            ->orderBy('Name')
            ->get(['Id', 'Name']);

        $tasks = DB::table('Task as task')
            ->leftJoin('Customer as customer', 'customer.Id', '=', 'task.CustomerId')
            ->leftJoin('Document as document', 'document.Id', '=', 'task.DocumentId')
            ->where('task.UserId', Auth::id())
            ->where('task.IsCompleted', false)
            ->orderBy('task.DueDate')
            ->orderByDesc('task.Id')
            ->get([
                'task.Id',
                'task.Title',
                'task.Description',
                'task.DueDate',
                'customer.Name as customer_name',
                'document.Number as document_number',
            ]);

        $dueTasks = $tasks->filter(fn ($task) => Carbon::parse($task->DueDate)->lessThanOrEqualTo($today));

        return view('livewire.dashboard', [
            'user' => $user,
            'stats' => $stats,
            'salesChart' => $salesChart,
            'startingCashEntries' => $startingCashEntries,
            'recentTransactions' => $recentTransactions,
            'accountCustomers' => $accountCustomers,
            'accountCustomer' => $accountCustomer,
            'unpaidSales' => $unpaidSales,
            'accountGrandTotal' => $accountGrandTotal,
            'accountTotalPayment' => $accountTotalPayment,
            'accountBalance' => $accountBalance,
            'taskCustomers' => $taskCustomers,
            'taskDocuments' => $taskDocuments,
            'taskProducts' => $taskProducts,
            'tasks' => $tasks,
            'dueTasks' => $dueTasks,
        ]);
    }

    private function buildSalesChart(): array
    {
        $startDate = Carbon::parse($this->chartStartDate ?: Carbon::today()->toDateString())->startOfDay();
        $endDate = Carbon::parse($this->chartEndDate ?: $startDate->toDateString())->endOfDay();

        if ($endDate->lessThan($startDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        $sales = DB::table('Document')
            ->where('DocumentTypeId', 2)
            ->whereBetween('DateCreated', [$startDate, $endDate])
            ->get(['DateCreated', 'Total']);

        if ($this->chartPeriod === 'Daily') {
            $selectedDate = Carbon::parse($this->chartStartDate ?: Carbon::today()->toDateString());
            $values = array_fill(0, 24, 0.0);
            $labels = [];

            for ($hour = 0; $hour < 24; $hour++) {
                $labels[] = Carbon::createFromTime($hour)->format('g A');
            }

            foreach ($sales as $sale) {
                $saleDate = Carbon::parse($sale->DateCreated);

                if ($saleDate->isSameDay($selectedDate)) {
                    $values[$saleDate->hour] += (float) $sale->Total;
                }
            }
        } elseif ($this->chartPeriod === 'Yearly') {
            $month = $startDate->copy()->startOfMonth();
            $lastMonth = $endDate->copy()->startOfMonth();
            $values = [];
            $labels = [];

            while ($month->lessThanOrEqualTo($lastMonth)) {
                $key = $month->format('Y-m');
                $labels[] = $month->format('M Y');
                $values[$key] = 0.0;
                $month->addMonth();
            }

            foreach ($sales as $sale) {
                $key = Carbon::parse($sale->DateCreated)->format('Y-m');

                if (array_key_exists($key, $values)) {
                    $values[$key] += (float) $sale->Total;
                }
            }

            $values = array_values($values);
        } else {
            $day = $startDate->copy()->startOfDay();
            $lastDay = $endDate->copy()->startOfDay();
            $values = [];
            $labels = [];

            while ($day->lessThanOrEqualTo($lastDay)) {
                $key = $day->toDateString();
                $labels[] = $day->format('M j');
                $values[$key] = 0.0;
                $day->addDay();
            }

            foreach ($sales as $sale) {
                $key = Carbon::parse($sale->DateCreated)->toDateString();

                if (array_key_exists($key, $values)) {
                    $values[$key] += (float) $sale->Total;
                }
            }

            $values = array_values($values);
        }

        $maximum = max($values ?: [0]);

        return [
            'labels' => $labels,
            'points' => collect($values)->map(fn (float $value, int $index) => [
                'label' => $labels[$index],
                'value' => $value,
                'height' => $maximum > 0 ? max(4, ($value / $maximum) * 100) : 4,
            ])->all(),
            'total' => array_sum($values),
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
        ];
    }
}
