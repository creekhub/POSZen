<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
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
    public string $calendarMonth = '';
    public bool $showCalendarCreateModal = false;
    public bool $showCalendarHolidayModal = false;
    public ?int $calendarHolidayId = null;
    public string $calendarHolidayName = '';
    public string $calendarHolidayDate = '';
    public string $calendarHolidayType = 'Regular';
    public ?int $viewingTaskId = null;
    public ?int $editingTaskId = null;
    public string $editTaskTitle = '';
    public string $editTaskDescription = '';
    public string $editTaskDueDate = '';

    public function mount(): void
    {
        $today = Carbon::today();
        $this->taskDueDate = $today->toDateString();
        $this->calendarMonth = $today->format('Y-m');
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
            'Description' => $this->sanitizeTaskDescription($validated['taskDescription']),
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
        $this->showCalendarCreateModal = false;
    }

    public function completeTask(int $taskId): void
    {
        DB::table('Task')
            ->where('Id', $taskId)
            ->where('IsCompleted', false)
            ->update([
                'IsCompleted' => true,
                'CompletedByUserId' => Auth::id(),
                'CompletedAt' => now(),
                'updated_at' => now(),
            ]);

        $this->showTaskReminder = false;
        if ($this->viewingTaskId === $taskId) {
            $this->viewingTaskId = null;
        }
    }

    public function openCalendarTaskForm(string $date): void
    {
        $validated = validator(
            ['calendarTaskDate' => $date],
            ['calendarTaskDate' => ['required', 'date']],
        )->validate();

        $this->reset([
            'taskTitle',
            'taskDescription',
            'taskCustomerId',
            'taskDocumentId',
            'taskCustomerSearch',
            'taskDocumentSearch',
        ]);
        $this->resetValidation();
        $this->taskDueDate = Carbon::parse($validated['calendarTaskDate'])->toDateString();
        $this->showCalendarCreateModal = true;
    }

    public function changeCalendarMonth(int $direction): void
    {
        if (! in_array($direction, [-1, 0, 1], true)) {
            return;
        }

        $this->calendarMonth = $direction === 0
            ? Carbon::today()->format('Y-m')
            : Carbon::parse($this->calendarMonth.'-01')->addMonths($direction)->format('Y-m');
    }

    public function openCalendarHolidayForm(?int $holidayId = null, ?string $date = null): void
    {
        $this->resetValidation();
        $this->calendarHolidayId = null;
        $this->calendarHolidayName = '';
        $this->calendarHolidayDate = $date ?? Carbon::today()->toDateString();
        $this->calendarHolidayType = 'Regular';

        if ($holidayId !== null) {
            $holiday = DB::table('CalendarHoliday')->where('Id', $holidayId)->first();

            if (! $holiday) {
                return;
            }

            $this->calendarHolidayId = (int) $holiday->Id;
            $this->calendarHolidayName = (string) $holiday->HolidayName;
            $this->calendarHolidayDate = Carbon::parse($holiday->Date)->toDateString();
            $this->calendarHolidayType = (string) $holiday->Type;
        }

        $this->showCalendarHolidayModal = true;
    }

    public function saveCalendarHoliday(): void
    {
        $validated = $this->validate([
            'calendarHolidayName' => ['required', 'string', 'max:255'],
            'calendarHolidayDate' => ['required', 'date'],
            'calendarHolidayType' => ['required', 'in:Regular,Special Non Working,Special Working'],
        ]);

        $holiday = [
            'HolidayName' => $validated['calendarHolidayName'],
            'Date' => Carbon::parse($validated['calendarHolidayDate'])->toDateString(),
            'Type' => $validated['calendarHolidayType'],
        ];

        if ($this->calendarHolidayId === null) {
            DB::table('CalendarHoliday')->insert($holiday);
            $this->taskMessage = 'Holiday created.';
        } else {
            DB::table('CalendarHoliday')->where('Id', $this->calendarHolidayId)->update($holiday);
            $this->taskMessage = 'Holiday updated.';
        }

        $this->showCalendarHolidayModal = false;
        $this->calendarHolidayId = null;
    }

    public function deleteCalendarHoliday(): void
    {
        if ($this->calendarHolidayId === null) {
            return;
        }

        DB::table('CalendarHoliday')->where('Id', $this->calendarHolidayId)->delete();
        $this->showCalendarHolidayModal = false;
        $this->calendarHolidayId = null;
        $this->taskMessage = 'Holiday deleted.';
    }

    public function closeCalendarHolidayForm(): void
    {
        $this->showCalendarHolidayModal = false;
        $this->calendarHolidayId = null;
        $this->resetValidation();
    }

    public function viewTask(int $taskId): void
    {
        $exists = DB::table('Task')
            ->where('Id', $taskId)
            ->where('IsCompleted', false)
            ->exists();

        if ($exists) {
            $this->cancelEditingTask();
            $this->viewingTaskId = $taskId;
            $this->startEditingTask($taskId);
        }
    }

    public function closeTaskDetails(): void
    {
        $this->cancelEditingTask();
        $this->viewingTaskId = null;
        $this->showCalendarCreateModal = false;
        $this->resetValidation();
    }

    public function startEditingTask(int $taskId): void
    {
        $task = DB::table('Task')
            ->from('Task as task')
            ->leftJoin('Customer as customer', 'customer.Id', '=', 'task.CustomerId')
            ->leftJoin('Document as document', 'document.Id', '=', 'task.DocumentId')
            ->where('task.Id', $taskId)
            ->where('task.UserId', Auth::id())
            ->where('task.IsCompleted', false)
            ->select([
                'task.Id',
                'task.Title',
                'task.Description',
                'task.DueDate',
                'task.CustomerId',
                'task.DocumentId',
                'customer.Name as customer_name',
                'document.Number as document_number',
            ])
            ->first();

        if (! $task) {
            return;
        }

        $this->editingTaskId = (int) $task->Id;
        $this->editTaskTitle = (string) $task->Title;
        $this->editTaskDescription = $this->sanitizeTaskDescription($task->Description) ?? '';
        $this->editTaskDueDate = (string) $task->DueDate;
        $this->taskCustomerId = $task->CustomerId ? (int) $task->CustomerId : null;
        $this->taskDocumentId = $task->DocumentId ? (int) $task->DocumentId : null;
        $this->taskCustomerSearch = (string) ($task->customer_name ?? '');
        $this->taskDocumentSearch = (string) ($task->document_number ?? '');
    }

    public function cancelEditingTask(): void
    {
        $this->reset(['editingTaskId', 'editTaskTitle', 'editTaskDescription', 'editTaskDueDate']);
        $this->reset(['taskCustomerId', 'taskDocumentId', 'taskCustomerSearch', 'taskDocumentSearch']);
    }

    public function updateTask(): void
    {
        if (! $this->editingTaskId) {
            return;
        }

        $validated = $this->validate([
            'editTaskTitle' => ['required', 'string', 'max:255'],
            'editTaskDescription' => ['nullable', 'string', 'max:5000'],
            'editTaskDueDate' => ['required', 'date'],
            'taskCustomerId' => ['nullable', 'integer', 'exists:Customer,Id'],
            'taskDocumentId' => ['nullable', 'integer', 'exists:Document,Id'],
        ]);

        $updated = DB::table('Task')
            ->where('Id', $this->editingTaskId)
            ->where('UserId', Auth::id())
            ->where('IsCompleted', false)
            ->update([
                'Title' => $validated['editTaskTitle'],
                'Description' => $this->sanitizeTaskDescription($validated['editTaskDescription']),
                'DueDate' => $validated['editTaskDueDate'],
                'CustomerId' => $validated['taskCustomerId'],
                'DocumentId' => $validated['taskDocumentId'],
                'updated_at' => now(),
            ]);

        if ($updated) {
            $this->taskMessage = 'Task updated.';
        }

        $this->cancelEditingTask();
    }

    public function dismissTaskReminder(): void
    {
        $this->showTaskReminder = false;
    }

    public function checkTaskReminder(): void
    {
        $hasDueTaskToday = DB::table('Task')
            ->where('IsCompleted', false)
            ->whereDate('DueDate', Carbon::today())
            ->exists();

        if ($hasDueTaskToday) {
            $this->showTaskReminder = true;
        }
    }

    public function render()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $sales = DB::table('Document')
            ->where('DocumentTypeId', 2);

        $todaySales = (clone $sales)
            ->whereDate('Date', $today)
            ->where('IsClockedOut', 0)
            ->sum('Total');

        $todayPurchases = DB::table('Document as document')
            ->join('Payment as payment', 'payment.DocumentId', '=', 'document.Id')
            ->join('PaymentType as payment_type', 'payment_type.Id', '=', 'payment.PaymentTypeId')
            ->where('document.DocumentTypeId', 1)
            ->whereDate('payment.Date', $today)
            ->whereRaw('LOWER(payment_type.Name) = ?', ['cash'])
            ->sum('payment.Amount');

        $todayOrderDiscount = (clone $sales)
            ->whereDate('Date', $today)
            ->where('IsClockedOut', 0)
            ->sum('Discount');

        $todayItemDiscount = DB::table('DocumentItem as item')
            ->join('Document as document', 'document.Id', '=', 'item.DocumentId')
            ->where('document.DocumentTypeId', 2)
            ->where('document.IsClockedOut', 0)
            ->whereDate('document.Date', $today)
            ->sum('item.Discount');

        $todayDiscount = (float) $todayOrderDiscount + (float) $todayItemDiscount;

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
            ->where('document.IsClockedOut', 0)
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
            ->where('document.IsClockedOut', 0)
            ->where('item.ProductCost', '>', 0)
            ->selectRaw('COALESCE(SUM((item.PriceAfterDiscount - item.ProductCost) * item.Quantity), 0) as profit')
            ->value('profit');

        $startingCashEntries = collect();
        $startingCashToday = 0.0;

        if (DB::getSchemaBuilder()->hasTable('StartingCash')) {
            $startingCashEntries = DB::table('StartingCash')
                ->whereDate('DateCreated', $today)
                ->whereNull('ZReportNumber')
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



        $creditCollected = DB::table('Document as d')
                        ->join('Payment as p', 'p.DocumentId', '=', 'd.Id')
                        ->where('d.IsClockedOut', 1)
                        ->whereDate('p.Date', $today)
                        ->sum('p.amount');

        $cashOnhand = ((float) $creditCollected + (float) $todaySales + (float) $startingCashToday) - ((float) $todayDiscount + (float) $todayReceivableTotal + (float) $todayPurchases);
        $startingCashAfterPurchases = (float) $startingCashToday - (float) $todayPurchases;

        $stats = [
            ['label' => 'Sales Today', 'value' => '₱'.number_format($todaySales, 2), 'trend' => $today->format('M j'), 'icon' => 'fa-chart-line', 'iconColor' => 'bg-emerald-50 text-emerald-700'],
            ['label' => 'Purchases Today', 'value' => '₱'.number_format($todayPurchases, 2), 'trend' => $today->format('M j'), 'icon' => 'fa-cart-shopping', 'iconColor' => 'bg-amber-50 text-amber-700'],
            ['label' => 'Discount Today', 'value' => '₱'.number_format($todayDiscount, 2), 'trend' => $today->format('M j'), 'icon' => 'fa-tags', 'iconColor' => 'bg-rose-50 text-rose-700'],
            ['label' => 'Receivables', 'value' => '₱'.number_format($receivableTotal, 2), 'trend' => 'Total outstanding', 'icon' => 'fa-file-invoice-dollar', 'iconColor' => 'bg-indigo-50 text-indigo-700'],
            ['label' => "Today's Receivables", 'value' => '₱'.number_format($todayReceivableTotal, 2), 'trend' => $today->format('M j'), 'icon' => 'fa-calendar-day', 'iconColor' => 'bg-sky-50 text-sky-700'],
            ['label' => "Today's Profit", 'value' => '₱'.number_format($todayProfit, 2), 'trend' => $today->format('M j'), 'icon' => 'fa-arrow-trend-up', 'iconColor' => 'bg-green-50 text-green-700'],
            ['label' => 'Starting Cash', 'value' => '₱'.number_format($startingCashAfterPurchases, 2), 'trend' => $today->format('M j'), 'icon' => 'fa-money-bill-wave', 'iconColor' => 'bg-slate-100 text-slate-700'],
            ['label' => 'Credit Collected', 'value' => '₱'.number_format($creditCollected, 2), 'trend' => $today->format('M j'), 'icon' => 'fa-credit-card', 'iconColor' => 'bg-cyan-50 text-cyan-700'],
            ['label' => 'Cash Onhand', 'value' => '₱'.number_format($cashOnhand, 2), 'trend' => $today->format('M j'), 'icon' => 'fa-wallet', 'iconColor' => 'bg-blue-50 text-blue-700'],

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

        $latestPurchases = DB::table('Document as document')
            ->leftJoin('Customer as supplier', 'supplier.Id', '=', 'document.CustomerId')
            ->where('document.DocumentTypeId', 1)
            ->select([
                'document.Id',
                'document.Number',
                'document.Date',
                'document.Total',
                'supplier.Name as supplier_name',
            ])
            ->orderByDesc('document.Date')
            ->orderByDesc('document.Id')
            ->limit(5)
            ->get();

        $latestPurchaseItems = DB::table('DocumentItem as item')
            ->join('Product as product', 'product.Id', '=', 'item.ProductId')
            ->whereIn('item.DocumentId', $latestPurchases->pluck('Id'))
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

        $latestPurchases->each(function ($purchase) use ($latestPurchaseItems): void {
            $purchase->items = $latestPurchaseItems->get($purchase->Id, collect());
        });

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
            ->where('task.IsCompleted', false)
            ->orderBy('task.DueDate')
            ->orderByDesc('task.Id')
            ->get([
                'task.Id',
                'task.UserId as user_id',
                'task.Title',
                'task.Description',
                'task.DueDate',
                'customer.Name as customer_name',
                'document.Number as document_number',
            ]);

        $tasks->each(function ($task): void {
            $task->Description = $this->sanitizeTaskDescription(str_replace(
                ['\\r\\n', '\\n', '\\r', "\r\n", "\r"],
                "\n",
                (string) $task->Description,
            ));
        });

        $dueTasks = $tasks->filter(fn ($task) => Carbon::parse($task->DueDate)->isSameDay($today));
        $calendarMonthDate = Carbon::parse($this->calendarMonth.'-01')->startOfMonth();
        $calendarStart = $calendarMonthDate->copy()->startOfWeek(Carbon::SUNDAY);
        $calendarEnd = $calendarStart->copy()->addDays(41);
        $calendarTaskGroups = $tasks->groupBy(fn ($task) => Carbon::parse($task->DueDate)->toDateString());
        $calendarHolidayGroups = DB::table('CalendarHoliday')
            ->whereDate('Date', '>=', $calendarStart->toDateString())
            ->whereDate('Date', '<=', $calendarEnd->toDateString())
            ->orderBy('Date')
            ->get(['Id', 'HolidayName', 'Date', 'Type'])
            ->groupBy(fn ($holiday) => Carbon::parse($holiday->Date)->toDateString());
        $calendarWeeks = collect(range(0, 41))
            ->map(function (int $offset) use ($calendarStart, $calendarMonthDate, $calendarTaskGroups, $calendarHolidayGroups): array {
                $date = $calendarStart->copy()->addDays($offset);
                $dateKey = $date->toDateString();

                return [
                    'date' => $dateKey,
                    'day' => $date->day,
                    'inMonth' => $date->month === $calendarMonthDate->month,
                    'isToday' => $date->isSameDay(Carbon::today()),
                    'tasks' => $calendarTaskGroups->get($dateKey, collect()),
                    'holidays' => $calendarHolidayGroups->get($dateKey, collect()),
                ];
            })
            ->chunk(7);
        $selectedTask = $tasks->firstWhere('Id', $this->viewingTaskId);

        return view('livewire.dashboard', [
            'user' => $user,
            'stats' => $stats,
            'startingCashEntries' => $startingCashEntries,
            'recentTransactions' => $recentTransactions,
            'latestPurchases' => $latestPurchases,
            'taskCustomers' => $taskCustomers,
            'taskDocuments' => $taskDocuments,
            'taskProducts' => $taskProducts,
            'tasks' => $tasks,
            'dueTasks' => $dueTasks,
            'calendarWeeks' => $calendarWeeks,
            'calendarMonthLabel' => $calendarMonthDate->format('F Y'),
            'calendarHolidayId' => $this->calendarHolidayId,
            'selectedTask' => $selectedTask,
        ]);
    }

    private function sanitizeTaskDescription(?string $description): ?string
    {
        $description = trim((string) $description);
        $sanitized = '';

        if ($description !== '') {
            if ($description === strip_tags($description)) {
                $sanitized = nl2br(e($description), false);
            } else {
                $document = new \DOMDocument('1.0', 'UTF-8');
                $previousErrorSetting = libxml_use_internal_errors(true);
                $document->loadHTML(
                    '<?xml encoding="UTF-8"><div>'.$description.'</div>',
                    LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
                );
                libxml_clear_errors();
                libxml_use_internal_errors($previousErrorSetting);

                $root = $document->getElementsByTagName('div')->item(0);

                if ($root) {
                    $this->sanitizeTaskNodes($root);

                    foreach ($root->childNodes as $child) {
                        $sanitized .= $document->saveHTML($child);
                    }
                } else {
                    $sanitized = e($description);
                }
            }
        }

        return trim(strip_tags($sanitized)) === '' ? null : $sanitized;
    }

    private function sanitizeTaskNodes(\DOMNode $parent): void
    {
        $allowedTags = ['a', 'br', 'em', 'li', 'ol', 'p', 's', 'strong', 'u', 'ul'];

        foreach (iterator_to_array($parent->childNodes) as $child) {
            if (! $child instanceof \DOMElement) {
                continue;
            }

            if (! in_array(strtolower($child->tagName), $allowedTags, true)) {
                $this->unwrapTaskElement($child);
                $this->sanitizeTaskNodes($parent);
                continue;
            }

            $this->sanitizeTaskAttributes($child);
            $this->sanitizeTaskNodes($child);
        }
    }

    private function unwrapTaskElement(\DOMElement $element): void
    {
        $parent = $element->parentNode;

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    private function sanitizeTaskAttributes(\DOMElement $element): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            if (strtolower($element->tagName) === 'a' && $attribute->name === 'href') {
                if (! $this->isSafeTaskLink($attribute->value)) {
                    $element->removeAttribute($attribute->name);
                }

                continue;
            }

            $element->removeAttribute($attribute->name);
        }
    }

    private function isSafeTaskLink(string $href): bool
    {
        $scheme = parse_url(trim($href), PHP_URL_SCHEME);

        return ! $scheme || in_array(strtolower($scheme), ['http', 'https', 'mailto'], true);
    }

}
