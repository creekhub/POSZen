<div class="min-h-screen bg-slate-100 p-6 text-slate-800">
    <div class="mx-auto max-w-7xl">
        <header class="mb-8 flex items-center justify-between rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div>
                <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Overview</p>
                <h1 class="mt-1 text-3xl font-bold">Dashboard</h1>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-sm text-slate-500">Welcome, {{ trim(($user?->FirstName ?? '').' '.($user?->LastName ?? '')) ?: 'Admin' }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                        Logout
                    </button>
                </form>
                {{-- <a href="{{ route('pos') }}" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                    Open POS
                </a> --}}
            </div>
        </header>

        <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($stats as $stat)
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm text-slate-500">{{ $stat['label'] }}</p>
                    <div class="mt-4 flex items-end justify-between">
                        <h2 class="text-3xl font-bold">{{ $stat['value'] }}</h2>
                        <span class="text-sm font-medium text-emerald-600">{{ $stat['trend'] }}</span>
                    </div>
                </div>
            @endforeach
        </section>

        @if ($showTaskReminder && $dueTasks->isNotEmpty())
            <div class="fixed inset-0 z-50 flex h-screen w-screen items-center justify-center overflow-hidden overscroll-contain bg-slate-950/60 p-4 pointer-events-auto" role="presentation" tabindex="-1">
                <section class="w-full max-w-2xl rounded-2xl border border-amber-200 bg-white p-5 shadow-2xl" role="alertdialog" aria-modal="true" aria-labelledby="task-reminder-title">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-700">Task reminder</p>
                            <h2 id="task-reminder-title" class="mt-1 text-2xl font-bold text-amber-950">{{ $dueTasks->count() }} task{{ $dueTasks->count() === 1 ? '' : 's' }} need{{ $dueTasks->count() === 1 ? 's' : '' }} attention</h2>
                        </div>
                        <button type="button" wire:click="dismissTaskReminder" aria-label="Close task reminders" class="rounded-lg border border-amber-300 px-3 py-2 text-xl font-semibold leading-none text-amber-800 hover:bg-amber-100">&times;</button>
                    </div>
                    <div class="mt-4 max-h-[60vh] space-y-3 overflow-y-auto pr-1">
                        @foreach ($dueTasks as $task)
                            <div class="flex items-start justify-between gap-3 rounded-xl bg-white/80 p-4 ring-1 ring-amber-200">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900">{{ $task->Title }}</p>
                                    <p class="mt-1 text-sm text-amber-800">Due {{ \Carbon\Carbon::parse($task->DueDate)->format('M j, Y') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $task->customer_name ?? 'No customer' }}{{ $task->document_number ? ' · '.$task->document_number : '' }}</p>
                                </div>
                                <button type="button" wire:click="completeTask({{ $task->Id }})" class="shrink-0 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Complete</button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" wire:click="dismissTaskReminder" class="mt-5 w-full rounded-xl border border-amber-300 px-4 py-3 text-sm font-semibold text-amber-900 hover:bg-amber-100">Review later</button>
                </section>
            </div>
        @endif

        <section class="mt-8 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
                <div>
                    <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Sales overview</p>
                    <h2 class="mt-1 text-2xl font-bold">{{ $chartPeriod }} sales</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        ₱{{ number_format($salesChart['total'], 2) }} from {{ \Carbon\Carbon::parse($salesChart['startDate'])->format('M j, Y') }} to {{ \Carbon\Carbon::parse($salesChart['endDate'])->format('M j, Y') }}
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div>
                        <label for="sales-period" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">View</label>
                        <select id="sales-period" wire:model.live="chartPeriod" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm font-medium outline-none focus:border-indigo-500 sm:w-32">
                            <option value="Daily">Daily</option>
                            <option value="Weekly">Weekly</option>
                            <option value="Monthly">Monthly</option>
                            <option value="Yearly">Yearly</option>
                        </select>
                    </div>
                    <div>
                        <label for="sales-start-date" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">From</label>
                        <input id="sales-start-date" type="date" wire:model.live="chartStartDate" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label for="sales-end-date" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">To</label>
                        <input id="sales-end-date" type="date" wire:model.live="chartEndDate" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                    </div>
                </div>
            </div>

            <div class="mt-8 overflow-x-auto pb-2">
                <div class="flex h-64 min-w-160 items-end gap-2 border-b border-l border-slate-200 px-3 pb-0 pt-6 sm:gap-3">
                    @foreach ($salesChart['points'] as $point)
                        <div class="group flex h-full min-w-6 flex-1 flex-col items-center justify-end gap-2" title="{{ $point['label'] }}: ₱{{ number_format($point['value'], 2) }}">
                            <span class="text-[10px] font-semibold text-slate-600">₱{{ number_format($point['value'], 0) }}</span>
                            <div class="w-full max-w-10 rounded-t-md bg-indigo-500 transition-colors group-hover:bg-indigo-700" style="height: {{ $point['height'] }}%"></div>
                            <span class="whitespace-nowrap text-[10px] text-slate-500">{{ $point['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="mt-8 grid gap-6 md:grid-cols-2">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h3 class="mb-4 text-xl font-bold">Recent Transactions</h3>
                <div class="space-y-3">
                    @forelse ($recentTransactions as $transaction)
                        <details class="group rounded-xl bg-slate-50 px-4 py-3">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                                <div>
                                    <p class="font-semibold">{{ $transaction->Number }}</p>
                                    <p class="text-sm text-slate-500">
                                        {{ $transaction->payment_type ?? 'Unpaid' }} · {{ $transaction->customer_name ?? 'Walk-in customer' }} · {{ \Carbon\Carbon::parse($transaction->Date)->format('M j, Y') }}
                                    </p>
                                </div>
                                <span class="shrink-0 font-bold text-slate-700">₱{{ number_format($transaction->Total, 2) }}</span>
                            </summary>
                            <div class="mt-3 border-t border-slate-200 pt-3">
                                <div class="space-y-1">
                                    @forelse ($transaction->items as $item)
                                        <div class="flex items-center justify-between gap-2 text-sm text-slate-600">
                                            <span class="min-w-0 truncate">{{ $item->product_name }} <span class="text-slate-400">× {{ rtrim(rtrim(number_format($item->Quantity, 2), '0'), '.') }}</span></span>
                                            <span class="shrink-0 text-right">
                                                ₱{{ number_format($item->Price, 2) }}
                                                @if ((float) $item->Discount > 0)
                                                    <span class="text-rose-600">· -₱{{ number_format($item->Discount, 2) }}</span>
                                                @endif
                                                <span class="font-medium text-slate-700">· ₱{{ number_format($item->Total, 2) }}</span>
                                            </span>
                                        </div>
                                    @empty
                                        <p class="text-sm text-slate-500">No product details available.</p>
                                    @endforelse
                                </div>
                            </div>
                        </details>
                    @empty
                        <p class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">No sales recorded yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h3 class="mb-4 text-xl font-bold">Starting Cash Entries</h3>
                <div class="space-y-3">
                    @forelse ($startingCashEntries as $entry)
                        <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                            <div>
                                <p class="font-semibold">{{ $entry->type_label ?? 'Starting Cash' }}</p>
                                <p class="text-sm text-slate-500">
                                    {{ \Carbon\Carbon::parse($entry->DateCreated)->format('M j, Y g:i A') }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">{{ $entry->description ?? 'No description' }}</p>
                            </div>
                            <span class="font-bold {{ $entry->StartingCashType == 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $entry->StartingCashType == 0 ? '' : '-' }}₱{{ number_format((float) ($entry->Amount ?? 0), 2) }}
                            </span>
                        </div>
                    @empty
                        <p class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">No starting cash entries recorded for today.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="mt-8 grid grid-cols-2 gap-6">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Quick action</p>
                <h2 class="mt-1 text-2xl font-bold">Create a task</h2>
                <form wire:submit="createTask" class="mt-5 space-y-4">
                    <div class="relative" data-task-mentions>
                        <label for="task-title" class="mb-2 block text-sm font-medium text-slate-600">Title</label>
                        <input id="task-title" wire:model="taskTitle" type="text" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-indigo-500" placeholder="Type / for products or @ for customers" autocomplete="off">
                        <div data-task-suggestions class="absolute left-0 right-0 z-20 hidden max-h-48 overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-lg"></div>
                        @error('taskTitle') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="task-due-date" class="mb-2 block text-sm font-medium text-slate-600">Reminder date</label>
                        <input id="task-due-date" wire:model="taskDueDate" type="date" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                        @error('taskDueDate') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="task-customer" class="mb-2 block text-sm font-medium text-slate-600">Customer</label>
                            <input id="task-customer" list="task-customer-options" wire:model.live="taskCustomerSearch" type="search" placeholder="Type customer name..." autocomplete="off" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                            <datalist id="task-customer-options">
                                @foreach ($taskCustomers as $customer)
                                    <option value="{{ $customer->Name }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div>
                            <label for="task-document" class="mb-2 block text-sm font-medium text-slate-600">Document</label>
                            <input id="task-document" list="task-document-options" wire:model.live="taskDocumentSearch" type="search" placeholder="Type document number..." autocomplete="off" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                            <datalist id="task-document-options">
                                @foreach ($taskDocuments as $document)
                                    <option value="{{ $document->Number }}" label="{{ $document->customer_name ?? 'Walk-in customer' }} · {{ \Carbon\Carbon::parse($document->Date)->format('M j, Y') }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                    </div>
                    <div class="relative" data-task-mentions>
                        <label for="task-description" class="mb-2 block text-sm font-medium text-slate-600">Notes</label>
                        <textarea id="task-description" wire:model="taskDescription" rows="3" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-indigo-500" placeholder="Type / for products or @ for customers"></textarea>
                        <div data-task-suggestions class="absolute left-0 right-0 z-20 hidden max-h-48 overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-lg"></div>
                    </div>
                    @if ($taskMessage)
                        <p class="text-sm font-medium text-emerald-700">{{ $taskMessage }}</p>
                    @endif
                    <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-700">Save task</button>
                </form>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Follow-up list</p>
                        <h2 class="mt-1 text-2xl font-bold">Open tasks</h2>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-600">{{ $tasks->count() }}</span>
                </div>
                <div class="mt-5 space-y-3">
                    @forelse ($tasks as $task)
                        <div class="flex items-start justify-between gap-4 rounded-xl bg-slate-50 px-4 py-3">
                            @if ($editingTaskId === (int) $task->Id)
                                <form wire:submit.prevent="updateTask" class="w-full space-y-3">
                                    <input wire:model="editTaskTitle" type="text" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500">
                                    @error('editTaskTitle') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                                    <textarea wire:model="editTaskDescription" rows="2" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500"></textarea>
                                    @error('editTaskDescription') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                                    <input wire:model="editTaskDueDate" type="date" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500">
                                    @error('editTaskDueDate') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                                    <div class="flex gap-2">
                                        <button type="button" wire:click="updateTask" wire:loading.attr="disabled" wire:target="updateTask" class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50">Save changes</button>
                                        <button type="button" wire:click="cancelEditingTask" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-white">Cancel</button>
                                    </div>
                                </form>
                            @else
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900">{{ $task->Title }}</p>
                                    <p class="mt-1 text-sm {{ \Carbon\Carbon::parse($task->DueDate)->lessThanOrEqualTo(\Carbon\Carbon::today()) ? 'font-semibold text-amber-700' : 'text-slate-500' }}">{{ \Carbon\Carbon::parse($task->DueDate)->format('M j, Y') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $task->customer_name ?? 'No customer' }}{{ $task->document_number ? ' · '.$task->document_number : '' }}</p>
                                    @if ($task->Description)
                                        <p class="mt-2 text-sm text-slate-600">{{ $task->Description }}</p>
                                    @endif
                                </div>
                                <div class="flex shrink-0 items-start gap-2">
                                    @if ((int) $task->user_id === (int) auth()->id())
                                        <button type="button" wire:click="startEditingTask({{ $task->Id }})" class="rounded-lg border border-indigo-300 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Edit</button>
                                    @endif
                                    <button type="button" wire:click="completeTask({{ $task->Id }})" class="rounded-lg border border-emerald-300 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Done</button>
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">No open tasks.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section id="state-of-account" class="mt-8 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Receivables</p>
                    <h2 class="mt-1 text-2xl font-bold">State of Account</h2>
                    <p class="mt-1 text-sm text-slate-500">Unpaid sales for the selected customer.</p>
                    <p id="soa-print-customer" class="mt-1">Customer: {{ $accountCustomer?->Name ?? 'No customer selected' }}</p>
                </div>

                <div id="soa-controls" class="flex w-full flex-col gap-3 sm:flex-row md:w-auto">
                    <div class="w-full sm:w-64">
                        <label for="account-customer" class="mb-2 block text-sm font-medium text-slate-600">Customer</label>
                        <input id="account-customer" list="account-customer-options" wire:model.live="accountCustomerSearch" type="search" placeholder="Type customer name..." class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-indigo-500" autocomplete="off">
                        <datalist id="account-customer-options">
                            @forelse ($accountCustomers as $customer)
                                <option value="{{ $customer->Name }}"></option>
                            @empty
                                <option value="No customers with unpaid sales"></option>
                            @endforelse
                        </datalist>
                    </div>
                    <button type="button" onclick="window.printStateOfAccount()" class="self-end rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                        Print SOA
                    </button>
                </div>
            </div>

            <div id="soa-summary" class="mt-6 grid gap-4 md:grid-cols-3">
                <div class="rounded-xl bg-slate-100 p-4">
                    <p class="text-sm text-slate-600">Grand Total</p>
                    <p class="mt-1 text-2xl font-bold text-slate-950">₱{{ number_format($accountGrandTotal, 2) }}</p>
                </div>
                <div class="rounded-xl bg-emerald-50 p-4">
                    <p class="text-sm text-emerald-700">Total Payment</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-950">₱{{ number_format($accountTotalPayment, 2) }}</p>
                </div>
                <div class="rounded-xl bg-rose-50 p-4">
                    <p class="text-sm text-rose-700">Grand Outstanding Balance</p>
                    <p class="mt-1 text-2xl font-bold text-rose-950">₱{{ number_format($accountBalance, 2) }}</p>
                </div>
            </div>

            <div class="mt-6 overflow-x-auto">
                <table id="soa-table" class="w-full min-w-160 text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-3 font-medium"><span class="soa-screen-header">Document</span><span class="soa-print-header">#</span></th>
                            <th class="px-3 py-3 font-medium"><span class="soa-screen-header">Sale Date</span><span class="soa-print-header">Date</span></th>
                            <th class="px-3 py-3 text-right font-medium">Payment</th>
                            <th class="px-3 py-3 text-right font-medium"><span class="soa-screen-header">Amount Due</span><span class="soa-print-header">Due</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($unpaidSales as $sale)
                            <tr class="bg-slate-50/70">
                                <td class="px-3 py-3 align-top font-semibold text-slate-800">{{ $sale->Number }}</td>
                                <td class="px-3 py-3 align-top text-slate-600">{{ \Carbon\Carbon::parse($sale->Date)->format('M j, Y') }}</td>
                                <td class="px-3 py-3 align-top text-right font-semibold text-emerald-700">
                                    @if ((float) $sale->paid_amount > 0)
                                        ₱{{ number_format($sale->paid_amount, 2) }}
                                    @endif
                                </td>
                                <td class="px-3 py-3 align-top text-right font-semibold text-rose-700">₱{{ number_format($sale->balance, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="px-3 pb-4 pt-0">
                                    <div class="ml-4 border-l-2 border-slate-200 pl-4">
                                        <div class="space-y-1">
                                            @foreach ($sale->items as $item)
                                                <div class="flex items-center justify-between gap-2 text-sm text-slate-600">
                                                    <span class="min-w-0 truncate">{{ $item->product_name }} <span class="text-slate-400">× {{ rtrim(rtrim(number_format($item->Quantity, 2), '0'), '.') }}</span></span>
                                                    <span class="shrink-0 text-right">
                                                        ₱{{ number_format($item->Price, 2) }}
                                                        @if ((float) $item->Discount > 0)
                                                            <span class="text-rose-600">· -₱{{ number_format($item->Discount, 2) }}</span>
                                                        @endif
                                                        <span class="font-medium text-slate-700">· ₱{{ number_format($item->Total, 2) }}</span>
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-8 text-center text-slate-500">This customer has no unpaid sales.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t-2 border-slate-300">
                        <tr>
                            <td colspan="3" class="px-3 py-2 text-right text-base font-bold text-slate-800">Grand Total</td>
                            <td class="px-3 py-2 text-right text-base font-bold text-slate-800">₱{{ number_format($accountGrandTotal, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="px-3 py-2 text-right text-base font-bold text-slate-800">Total Payment</td>
                            <td class="px-3 py-2 text-right text-base font-bold text-emerald-700">₱{{ number_format($accountTotalPayment, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="px-3 py-2 text-right text-base font-bold text-slate-800">Outstanding Balance</td>
                            <td class="px-3 py-2 text-right text-base font-bold text-rose-700">₱{{ number_format($accountBalance, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    </div>
</div>

<style>
    @page {
        size: 65mm auto;
        margin: 0;
    }

    .soa-print-header {
        display: none;
    }

    @media print {
        html,
        body {
            width: 65mm;
            min-width: 65mm;
            height: auto;
            min-height: 0;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        body > div,
        body > div > div,
        .min-h-screen,
        .min-h-screen > .mx-auto {
            width: 65mm;
            min-width: 65mm;
            height: auto !important;
            min-height: 0 !important;
            margin: 0;
            padding: 0;
        }

        body * {
            visibility: hidden;
        }

        #state-of-account,
        #state-of-account * {
            visibility: visible;
            color: #000 !important;
            border-color: #000 !important;
        }

        #state-of-account {
            position: absolute;
            top: 0;
            left: 0;
            margin: 0 !important;
            width: 65mm;
            min-width: 65mm;
            padding: 2mm;
            box-sizing: border-box;
            border: 0;
            border-radius: 0;
            box-shadow: none;
            color: #000;
            font-size: 10.6pt;
        }

        #soa-controls,
        #account-customer,
        #soa-summary,
        #state-of-account .overflow-x-auto {
            display: none;
        }

        #state-of-account > div:first-child {
            display: block;
            margin: 0 0 3mm;
            text-align: center;
        }

        #state-of-account > div:first-child > div:first-child p {
            margin: 0;
            color: #000;
            font-size: 8.24pt;
            letter-spacing: 0.12em;
        }

        #state-of-account h2 {
            margin: 1mm 0 0;
            font-size: 16.47pt;
        }

        #state-of-account > div:first-child > div:first-child p:nth-of-type(2) {
            margin-top: 1mm;
            color: #000;
            font-size: 9.41pt;
        }

        #soa-print-customer {
            display: block;
            margin-top: 1mm;
            color: #000;
            font-size: 9.41pt;
            font-weight: 700;
        }

        #state-of-account > .mt-6.overflow-x-auto {
            display: block;
            margin: 0;
            overflow: visible;
            width: 100%;
        }

        #soa-table {
            display: table;
            width: 100%;
            min-width: 0;
            table-layout: fixed;
            border-collapse: collapse;
            font-size: 8.24pt;
        }

        #soa-table th,
        #soa-table td {
            padding: 1.5mm 0.5mm;
            color: #000;
            vertical-align: top;
            overflow-wrap: anywhere;
        }

        #soa-table th:nth-child(1),
        #soa-table td:nth-child(1) {
            width: 27%;
        }

        #soa-table th:nth-child(2),
        #soa-table td:nth-child(2) {
            width: 23%;
        }

        #soa-table th:nth-child(3),
        #soa-table td:nth-child(3),
        #soa-table th:nth-child(4),
        #soa-table td:nth-child(4) {
            width: 25%;
        }

        .soa-screen-header {
            display: none;
        }

        .soa-print-header {
            display: inline;
        }

        #soa-table thead {
            border-bottom: 1px solid #000;
        }

        #soa-table thead th {
            font-size: 7.06pt;
            font-weight: 700;
            letter-spacing: 0;
        }

        #soa-table tbody tr.bg-slate-50\/70 {
            background: transparent;
        }

        #soa-table tbody tr:nth-child(2n) {
            border-bottom: 1px dashed #000;
        }

        #soa-table tbody tr:nth-child(2n) td {
            padding-top: 0;
            padding-bottom: 2mm;
        }

        #soa-table tbody tr:nth-child(2n) td > div {
            margin-left: 0;
            padding-left: 0;
            border-left: 0;
        }

        #soa-table tbody tr:nth-child(2n) p {
            margin-bottom: 1mm;
            color: #000;
            font-size: 7.06pt;
        }

        #soa-table tbody tr:nth-child(2n) .space-y-1 > div {
            font-size: 8.65pt;
        }

        #soa-table tfoot {
            display: table-row-group;
            border-top: 1px solid #000;
            break-inside: avoid;
        }

        #soa-table tfoot td {
            padding-top: 2mm;
            font-size: 9.41pt;
            font-weight: 700;
        }

        #soa-table .text-right {
            text-align: right;
        }

        #state-of-account button,
        #state-of-account input,
        #state-of-account label,
        #state-of-account datalist {
            display: none;
        }
    }
</style>

<script>
    window.taskMentionData = {
        products: @json($taskProducts->map(fn ($product) => ['name' => $product->Name])->values()),
        customers: @json($taskCustomers->map(fn ($customer) => ['name' => $customer->Name])->values()),
    };

    window.printStateOfAccount = function () {
        window.print();
    };

    const syncTaskReminderBodyLock = () => {
        const isOpen = Boolean(document.querySelector('[role="alertdialog"]'));
        document.body.classList.toggle('overflow-hidden', isOpen);
        document.documentElement.classList.toggle('overflow-hidden', isOpen);
    };

    if (!window.taskReminderLockObserver) {
        window.taskReminderLockObserver = new MutationObserver(syncTaskReminderBodyLock);
        window.taskReminderLockObserver.observe(document.body, { childList: true, subtree: true });
    }

    syncTaskReminderBodyLock();

    const checkTaskReminderEveryThirtyMinutes = () => {
        if (document.querySelector('[role="alertdialog"]')) {
            return;
        }

        const dashboardRoot = document.querySelector('[wire\\:id]');
        const componentId = dashboardRoot?.getAttribute('wire:id');

        if (componentId) {
            Livewire.find(componentId)?.$call('checkTaskReminder');
        }
    };

    if (!window.taskReminderInterval) {
        window.taskReminderInterval = window.setInterval(checkTaskReminderEveryThirtyMinutes, 30 * 60 * 1000);
    }

    (function () {
        const getContext = (textarea) => {
            const beforeCursor = textarea.value.slice(0, textarea.selectionStart);
            const match = beforeCursor.match(/(?:^|\s)([/@])([^\s]*)$/);

            if (!match) {
                return null;
            }

            return {
                trigger: match[1],
                query: match[2].toLowerCase(),
                start: beforeCursor.length - match[0].length + (match[0][0] === ' ' ? 1 : 0),
                end: textarea.selectionStart,
            };
        };

        const closeSuggestions = (container) => {
            const suggestions = container.querySelector('[data-task-suggestions]');

            if (suggestions) {
                suggestions.classList.add('hidden');
                suggestions.innerHTML = '';
            }
        };

        const showSuggestions = (textarea) => {
            const container = textarea.closest('[data-task-mentions]');
            const suggestions = container?.querySelector('[data-task-suggestions]');
            const context = getContext(textarea);

            if (!container || !suggestions || !context) {
                if (container) {
                    closeSuggestions(container);
                }

                return;
            }

            const source = context.trigger === '/'
                ? (window.taskMentionData?.products || [])
                : (window.taskMentionData?.customers || []);
            const matches = source
                .filter((item) => item.name.toLowerCase().includes(context.query))
                .slice(0, 8);

            if (!matches.length) {
                closeSuggestions(container);
                return;
            }

            suggestions.innerHTML = matches.map((item) => `
                <button type="button" data-task-suggestion="${item.name.replace(/"/g, '&quot;')}" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">
                    <span class="mr-2 font-semibold text-indigo-600">${context.trigger}</span>${item.name}
                </button>
            `).join('');
            suggestions.classList.remove('hidden');
        };

        document.addEventListener('input', (event) => {
            if (event.target.matches('[data-task-mentions] input, [data-task-mentions] textarea')) {
                showSuggestions(event.target);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && event.target.matches('[data-task-mentions] input, [data-task-mentions] textarea')) {
                closeSuggestions(event.target.closest('[data-task-mentions]'));
            }
        });

        document.addEventListener('click', (event) => {
            const suggestion = event.target.closest('[data-task-suggestion]');

            if (!suggestion) {
                return;
            }

            const container = suggestion.closest('[data-task-mentions]');
            const field = container?.querySelector('input, textarea');
            const context = field ? getContext(field) : null;

            if (!field || !context) {
                return;
            }

            const name = suggestion.dataset.taskSuggestion;
            field.setRangeText(`${context.trigger}${name} `, context.start, context.end, 'end');
            field.dispatchEvent(new Event('input', { bubbles: true }));
            closeSuggestions(container);
            field.focus();
        });

        document.addEventListener('click', (event) => {
            document.querySelectorAll('[data-task-mentions]').forEach((container) => {
                if (!container.contains(event.target)) {
                    closeSuggestions(container);
                }
            });
        });
    })();

    document.addEventListener('livewire:init', function () {
        if (window.dashboardEventSource) {
            return;
        }

        window.dashboardEventSource = new EventSource('{{ route('dashboard.events') }}');
        window.dashboardEventSource.addEventListener('dashboard.updated', function () {
            const dashboardRoot = document.querySelector('[wire\\:id]');
            const componentId = dashboardRoot?.getAttribute('wire:id');

            if (componentId) {
                Livewire.find(componentId)?.$refresh();
            }
        });
    });
</script>
