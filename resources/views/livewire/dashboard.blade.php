<div class="min-h-screen bg-slate-100 p-6 text-slate-800">
    <div class="mx-auto max-w-7xl">
        @include('livewire.partials.navigation')
        <header class="mb-8 flex items-center justify-between rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div>
                <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Overview</p>
                <h1 class="mt-1 text-3xl font-bold">Dashboard</h1>
            </div>
        </header>

        <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($stats as $stat)
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm text-slate-500">{{ $stat['label'] }}</p>
                        <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl {{ $stat['iconColor'] }}" aria-hidden="true">
                            <i class="fa-solid {{ $stat['icon'] }} text-lg"></i>
                        </span>
                    </div>
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

        <section class="mt-8 grid gap-6 md:grid-cols-2">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h3 class="mb-4 text-xl font-bold">Recent Transactions</h3>
                <div class="space-y-3">
                    @forelse ($recentTransactions as $transaction)
                        <details class="group rounded-xl bg-slate-50 px-4 py-3">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                                <div>
                                    <p class="font-semibold">{{ $transaction->Number }}</p>
                                    <p class="text-sm text-slate-500">{{ $transaction->payment_type ?? 'Unpaid' }} · {{ $transaction->customer_name ?? 'Walk-in customer' }} · {{ \Carbon\Carbon::parse($transaction->Date)->format('M j, Y') }}</p>
                                </div>
                                <span class="shrink-0 font-bold text-slate-700">₱{{ number_format($transaction->Total, 2) }}</span>
                            </summary>
                            <div class="mt-3 border-t border-slate-200 pt-3">
                                <div class="space-y-1">
                                    @forelse ($transaction->items as $item)
                                        <div class="flex items-center justify-between gap-2 text-sm text-slate-600">
                                            <span class="min-w-0 truncate">{{ $item->product_name }} <span class="text-slate-400">× {{ rtrim(rtrim(number_format($item->Quantity, 2), '0'), '.') }}</span></span>
                                            <span class="shrink-0 text-right">₱{{ number_format($item->Price, 2) }} @if ((float) $item->Discount > 0)<span class="text-rose-600">· -₱{{ number_format($item->Discount, 2) }}</span>@endif <span class="font-medium text-slate-700">· ₱{{ number_format($item->Total, 2) }}</span></span>
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
                <h3 class="mb-4 text-xl font-bold">Latest Purchased</h3>
                <div class="space-y-3">
                    @forelse ($latestPurchases as $purchase)
                        <details class="group rounded-xl bg-slate-50 px-4 py-3">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                                <div>
                                    <p class="font-semibold">{{ $purchase->Number }}</p>
                                    <p class="text-sm text-slate-500">{{ $purchase->supplier_name ?? 'No supplier' }} · {{ \Carbon\Carbon::parse($purchase->Date)->format('M j, Y') }}</p>
                                </div>
                                <span class="shrink-0 font-bold text-slate-700">₱{{ number_format($purchase->Total, 2) }}</span>
                            </summary>
                            <div class="mt-3 border-t border-slate-200 pt-3">
                                <div class="space-y-1">
                                    @forelse ($purchase->items as $item)
                                        <div class="flex items-center justify-between gap-2 text-sm text-slate-600">
                                            <span class="min-w-0 truncate">{{ $item->product_name }} <span class="text-slate-400">× {{ rtrim(rtrim(number_format($item->Quantity, 2), '0'), '.') }}</span></span>
                                            <span class="shrink-0 text-right">₱{{ number_format($item->Price, 2) }} @if ((float) $item->Discount > 0)<span class="text-rose-600">· -₱{{ number_format($item->Discount, 2) }}</span>@endif <span class="font-medium text-slate-700">· ₱{{ number_format($item->Total, 2) }}</span></span>
                                        </div>
                                    @empty
                                        <p class="text-sm text-slate-500">No product details available.</p>
                                    @endforelse
                                </div>
                            </div>
                        </details>
                    @empty
                        <p class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">No purchases recorded yet.</p>
                    @endforelse
                </div>
            </div>

        </section>

        <section class="mt-6 max-w-2xl">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h3 class="mb-4 text-xl font-bold">Starting Cash Entries</h3>
                <div class="space-y-3">
                    @forelse ($startingCashEntries as $entry)
                        <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                            <div>
                                <p class="font-semibold">{{ $entry->type_label ?? 'Starting Cash' }}</p>
                                <p class="text-sm text-slate-500">{{ \Carbon\Carbon::parse($entry->DateCreated)->format('M j, Y g:i A') }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $entry->description ?? 'No description' }}</p>
                            </div>
                            <span class="font-bold {{ $entry->StartingCashType == 0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ $entry->StartingCashType == 0 ? '' : '-' }}₱{{ number_format((float) ($entry->Amount ?? 0), 2) }}</span>
                        </div>
                    @empty
                        <p class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">No starting cash entries recorded for today.</p>
                    @endforelse
                </div>
            </div>

        </section>

                <section class="mt-8 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <header class="mb-5 flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Task schedule</p>
                            <h2 class="mt-1 text-2xl font-bold">{{ $calendarMonthLabel }}</h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="changeCalendarMonth(-1)" aria-label="Previous month" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">&larr;</button>
                            <button type="button" wire:click="changeCalendarMonth(0)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Today</button>
                            <button type="button" wire:click="changeCalendarMonth(1)" aria-label="Next month" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">&rarr;</button>
                            <button type="button" wire:click="openCalendarHolidayForm" class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-900 hover:bg-amber-100">New holiday</button>
                            <button type="button" wire:click="openCalendarTaskForm('{{ \Carbon\Carbon::today()->toDateString() }}')" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">New task</button>
                        </div>
                    </header>

                    <div class="grid grid-cols-7 border-l border-t border-slate-200">
                        @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday)
                            <div class="border-b border-r border-slate-200 bg-slate-50 px-1 py-2 text-center text-xs font-semibold text-slate-600 sm:px-3">{{ $weekday }}</div>
                        @endforeach
                        @foreach ($calendarWeeks as $week)
                            @foreach ($week as $day)
                                <div wire:key="calendar-day-{{ $day['date'] }}" @class([
                                    'min-h-28 border-b border-r border-slate-200 p-1 sm:p-2',
                                    'bg-white' => $day['inMonth'],
                                    'bg-slate-50 text-slate-400' => ! $day['inMonth'],
                                ])>
                                    <button type="button" wire:click="openCalendarTaskForm('{{ $day['date'] }}')" title="Create task on {{ \Carbon\Carbon::parse($day['date'])->format('F j, Y') }}" @class([
                                        'mb-1 flex size-7 items-center justify-center rounded-full text-xs font-semibold',
                                        'bg-slate-900 text-white' => $day['isToday'],
                                        'text-slate-700 hover:bg-slate-100' => ! $day['isToday'] && $day['inMonth'],
                                        'text-slate-400 hover:bg-slate-100' => ! $day['inMonth'],
                                    ])>{{ $day['day'] }}</button>
                                    <div class="space-y-1">
                                        @foreach ($day['holidays'] as $calendarHoliday)
                                            @php
                                                $holidayIcon = match ($calendarHoliday->Type) {
                                                    'Regular' => 'fa-flag',
                                                    'Special Non Working' => 'fa-mug-hot',
                                                    'Special Working' => 'fa-briefcase',
                                                    default => 'fa-calendar-days',
                                                };
                                            @endphp
                                            <button type="button" wire:key="calendar-holiday-{{ $calendarHoliday->Id }}" wire:click="openCalendarHolidayForm({{ $calendarHoliday->Id }})" title="Edit {{ $calendarHoliday->HolidayName }}" @class([
                                                'flex w-full items-start gap-1.5 wrap-break-word rounded px-1 py-1 text-left text-[10px] font-semibold leading-tight sm:px-2 sm:text-xs',
                                                'bg-rose-100 text-rose-900 hover:bg-rose-200' => $calendarHoliday->Type === 'Regular',
                                                'bg-amber-100 text-amber-900 hover:bg-amber-200' => $calendarHoliday->Type === 'Special Non Working',
                                                'bg-sky-100 text-sky-900 hover:bg-sky-200' => $calendarHoliday->Type === 'Special Working',
                                                'bg-slate-100 text-slate-900 hover:bg-slate-200' => ! in_array($calendarHoliday->Type, ['Regular', 'Special Non Working', 'Special Working'], true),
                                            ])>
                                                <i class="fa-solid {{ $holidayIcon }} mt-0.5 shrink-0" aria-hidden="true"></i>
                                                <span class="min-w-0">{{ $calendarHoliday->HolidayName }} <span class="font-normal">({{ $calendarHoliday->Type }})</span></span>
                                            </button>
                                        @endforeach
                                        @foreach ($day['tasks'] as $calendarTask)
                                            <button type="button" wire:key="calendar-task-{{ $calendarTask->Id }}" wire:click="viewTask({{ $calendarTask->Id }})" @class([
                                                'flex w-full items-start gap-1.5 wrap-break-word rounded px-1 py-1 text-left text-[10px] font-medium leading-tight sm:px-2 sm:text-xs',
                                                'bg-red-100 text-red-800 hover:bg-red-200' => \Carbon\Carbon::parse($calendarTask->DueDate)->lt(\Carbon\Carbon::today()),
                                                'bg-emerald-100 text-emerald-900 hover:bg-emerald-200' => ! \Carbon\Carbon::parse($calendarTask->DueDate)->lt(\Carbon\Carbon::today()),
                                            ])>
                                                <i class="fa-solid fa-list-check mt-0.5 shrink-0" aria-hidden="true"></i>
                                                <span class="min-w-0">{{ $calendarTask->Title }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                    @if ($taskMessage)
                        <output class="mt-4 block text-sm font-medium text-emerald-700">{{ $taskMessage }}</output>
                    @endif
                </section>

                @if ($showCalendarCreateModal)
                    <dialog open data-task-modal wire:keydown.escape.window="closeTaskDetails" class="fixed inset-0 z-50 m-0 flex h-full w-full max-w-none items-center justify-center overflow-y-auto border-0 bg-slate-950/60 p-4" aria-modal="true" aria-labelledby="calendar-create-title">
                        <section class="my-auto max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white p-5 shadow-2xl">
                            <header class="mb-4 flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold uppercase tracking-[0.15em] text-slate-500">Calendar task</p>
                                    <h2 id="calendar-create-title" class="mt-1 text-xl font-bold">Create a task</h2>
                                </div>
                                <button type="button" wire:click="closeTaskDetails" aria-label="Close create task dialog" class="rounded-lg border border-slate-300 px-3 py-2 text-xl font-semibold leading-none text-slate-700 hover:bg-slate-50">&times;</button>
                            </header>
                            <form wire:submit="createTask" class="space-y-4">
                                <div class="relative" data-task-mentions>
                                    <label for="task-title" class="mb-2 block text-sm font-medium text-slate-600">Title</label>
                                    <input id="task-title" wire:model="taskTitle" type="text" class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-indigo-500" placeholder="Type / for products or @ for customers" autocomplete="off">
                                    <div data-task-suggestions class="absolute left-0 right-0 z-20 hidden max-h-48 overflow-y-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg"></div>
                                    @error('taskTitle') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="task-due-date" class="mb-2 block text-sm font-medium text-slate-600">Due date</label>
                                    <input id="task-due-date" wire:model="taskDueDate" type="date" class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                                    @error('taskDueDate') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="task-customer" class="mb-2 block text-sm font-medium text-slate-600">Customer</label>
                                        <input id="task-customer" list="task-customer-options" wire:model.live="taskCustomerSearch" type="search" placeholder="Type customer name..." autocomplete="off" class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                                        <datalist id="task-customer-options">
                                            @foreach ($taskCustomers as $customer)
                                                <option value="{{ $customer->Name }}"></option>
                                            @endforeach
                                        </datalist>
                                    </div>
                                    <div>
                                        <label for="task-document" class="mb-2 block text-sm font-medium text-slate-600">Document</label>
                                        <input id="task-document" list="task-document-options" wire:model.live="taskDocumentSearch" type="search" placeholder="Type document number..." autocomplete="off" class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                                        <datalist id="task-document-options">
                                            @foreach ($taskDocuments as $document)
                                                <option value="{{ $document->Number }}" label="{{ $document->customer_name ?? 'Walk-in customer' }} · {{ \Carbon\Carbon::parse($document->Date)->format('M j, Y') }}"></option>
                                            @endforeach
                                        </datalist>
                                    </div>
                                </div>
                                <div class="relative" data-task-mentions>
                                    <label for="task-description-editor" class="mb-2 block text-sm font-medium text-slate-600">Notes</label>
                                    <div class="task-editor rounded-lg border border-slate-300 bg-white" data-task-editor-container data-livewire-property="taskDescription" data-initial-content="{{ $taskDescription }}">
                                        <div id="task-description-editor" data-task-editor wire:ignore hidden aria-label="Task notes"></div>
                                    </div>
                                    <div data-task-suggestions class="absolute left-0 right-0 z-20 hidden max-h-48 overflow-y-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg"></div>
                                </div>
                                <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-700">Save task</button>
                            </form>
                        </section>
                    </dialog>
                @endif

                @if ($showCalendarHolidayModal)
                    <dialog open data-task-modal wire:keydown.escape.window="closeCalendarHolidayForm" class="fixed inset-0 z-50 m-0 flex h-full w-full max-w-none items-center justify-center overflow-y-auto border-0 bg-slate-950/60 p-4" aria-modal="true" aria-labelledby="calendar-holiday-title">
                        <section class="my-auto w-full max-w-lg rounded-xl bg-white p-5 shadow-2xl">
                            <header class="mb-4 flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold uppercase tracking-[0.15em] text-amber-700">Calendar holiday</p>
                                    <h2 id="calendar-holiday-title" class="mt-1 text-xl font-bold">{{ $calendarHolidayId ? 'Edit holiday' : 'Create a holiday' }}</h2>
                                </div>
                                <button type="button" wire:click="closeCalendarHolidayForm" aria-label="Close holiday dialog" class="rounded-lg border border-slate-300 px-3 py-2 text-xl font-semibold leading-none text-slate-700 hover:bg-slate-50">&times;</button>
                            </header>
                            <form wire:submit="saveCalendarHoliday" class="space-y-4">
                                <div>
                                    <label for="calendar-holiday-name" class="mb-1 block text-sm font-medium text-slate-600">Name</label>
                                    <input id="calendar-holiday-name" wire:model="calendarHolidayName" type="text" maxlength="255" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-amber-500">
                                    @error('calendarHolidayName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="calendar-holiday-date" class="mb-1 block text-sm font-medium text-slate-600">Date</label>
                                        <input id="calendar-holiday-date" wire:model="calendarHolidayDate" type="date" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-amber-500">
                                        @error('calendarHolidayDate') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="calendar-holiday-type" class="mb-1 block text-sm font-medium text-slate-600">Type</label>
                                        <select id="calendar-holiday-type" wire:model="calendarHolidayType" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-amber-500">
                                            <option value="Regular">Regular</option>
                                            <option value="Special Non Working">Special Non Working</option>
                                            <option value="Special Working">Special Working</option>
                                        </select>
                                        @error('calendarHolidayType') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    @if ($calendarHolidayId)
                                        <button type="button" wire:click="deleteCalendarHoliday" wire:confirm="Delete this holiday?" class="rounded-lg border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Delete</button>
                                    @else
                                        <span></span>
                                    @endif
                                    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Save holiday</button>
                                </div>
                            </form>
                        </section>
                    </dialog>
                @endif

                @if ($selectedTask)
                    <dialog open data-task-modal wire:keydown.escape.window="closeTaskDetails" class="fixed inset-0 z-50 m-0 flex h-full w-full max-w-none items-center justify-center overflow-y-auto border-0 bg-slate-950/60 p-4" aria-modal="true" aria-labelledby="task-details-title">
                        <section class="my-auto max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white p-5 shadow-2xl">
                            <header class="mb-5 flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold uppercase tracking-[0.15em] text-slate-500">Task details</p>
                                    <h2 id="task-details-title" class="mt-1 text-xl font-bold">{{ $selectedTask->Title }}</h2>
                                </div>
                                <button type="button" wire:click="closeTaskDetails" aria-label="Close task details" class="rounded-lg border border-slate-300 px-3 py-2 text-xl font-semibold leading-none text-slate-700 hover:bg-slate-50">&times;</button>
                            </header>
                            @if ($editingTaskId === (int) $selectedTask->Id)
                                <form wire:submit.prevent="updateTask" class="space-y-4">
                                    <div>
                                        <label for="edit-task-title" class="mb-1 block text-sm font-medium text-slate-600">Title</label>
                                        <input id="edit-task-title" wire:model="editTaskTitle" type="text" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500">
                                        @error('editTaskTitle') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label for="edit-task-customer" class="mb-1 block text-sm font-medium text-slate-600">Customer</label>
                                            <input id="edit-task-customer" list="task-customer-options" wire:model.live="taskCustomerSearch" type="search" placeholder="Type customer name..." autocomplete="off" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500">
                                            @error('taskCustomerId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="edit-task-document" class="mb-1 block text-sm font-medium text-slate-600">Document</label>
                                            <input id="edit-task-document" list="task-document-options" wire:model.live="taskDocumentSearch" type="search" placeholder="Type document number..." autocomplete="off" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500">
                                            @error('taskDocumentId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                    <div>
                                        <label for="edit-task-due-date" class="mb-1 block text-sm font-medium text-slate-600">Due date</label>
                                        <input id="edit-task-due-date" wire:model="editTaskDueDate" type="date" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500">
                                        @error('editTaskDueDate') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <datalist id="task-customer-options">
                                        @foreach ($taskCustomers as $customer)
                                            <option value="{{ $customer->Name }}"></option>
                                        @endforeach
                                    </datalist>
                                    <datalist id="task-document-options">
                                        @foreach ($taskDocuments as $document)
                                            <option value="{{ $document->Number }}"></option>
                                        @endforeach
                                    </datalist>
                                    <div class="relative" data-task-mentions>
                                        <label for="edit-task-notes-editor" class="mb-1 block text-sm font-medium text-slate-600">Notes</label>
                                        <div class="task-editor rounded-lg border border-slate-300 bg-white" data-task-editor-container data-livewire-property="editTaskDescription" data-initial-content="{{ $editTaskDescription }}">
                                            <div id="edit-task-notes-editor" data-task-editor wire:ignore hidden aria-label="Task notes"></div>
                                        </div>
                                        <div data-task-suggestions class="absolute left-0 right-0 z-20 hidden max-h-48 overflow-y-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg"></div>
                                        @error('editTaskDescription') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="submit" wire:loading.attr="disabled" wire:target="updateTask" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50">Save changes</button>
                                        <button type="button" wire:click="completeTask({{ $selectedTask->Id }})" class="rounded-lg border border-emerald-300 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">Mark complete</button>
                                    </div>
                                </form>
                            @else
                                <dl class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <dt class="text-xs font-semibold uppercase text-slate-500">Due date</dt>
                                        <dd class="mt-1 text-sm text-slate-900">{{ \Carbon\Carbon::parse($selectedTask->DueDate)->format('M j, Y') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-semibold uppercase text-slate-500">Customer</dt>
                                        <dd class="mt-1 text-sm text-slate-900">{{ $selectedTask->customer_name ?? 'No customer' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-semibold uppercase text-slate-500">Document</dt>
                                        <dd class="mt-1 text-sm text-slate-900">{{ $selectedTask->document_number ?? 'No document' }}</dd>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <dt class="text-xs font-semibold uppercase text-slate-500">Notes</dt>
                                        @if ($selectedTask->Description)
                                            <dd class="task-description mt-1 wrap-break-word text-sm text-slate-700">{!! $selectedTask->Description !!}</dd>
                                        @else
                                            <dd class="mt-1 text-sm text-slate-500">No notes.</dd>
                                        @endif
                                    </div>
                                </dl>
                                <div class="mt-6 flex flex-wrap gap-2">
                                    @if ((int) $selectedTask->user_id === (int) auth()->id())
                                        <button type="button" wire:click="startEditingTask({{ $selectedTask->Id }})" class="rounded-lg border border-indigo-300 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">Edit task</button>
                                    @endif
                                    <button type="button" wire:click="completeTask({{ $selectedTask->Id }})" class="rounded-lg border border-emerald-300 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">Mark complete</button>
                                </div>
                            @endif
                        </section>
                    </dialog>
                @endif

    </div>

<script>
    window.taskMentionData = {
        products: @json($taskProducts->map(fn ($product) => ['name' => $product->Name])->values()),
        customers: @json($taskCustomers->map(fn ($customer) => ['name' => $customer->Name])->values()),
    };

    const syncTaskReminderBodyLock = () => {
        const isOpen = Boolean(document.querySelector('[role="alertdialog"], [data-task-modal]'));
        document.body.classList.toggle('overflow-hidden', isOpen);
        document.documentElement.classList.toggle('overflow-hidden', isOpen);
    };

    if (!window.taskReminderLockObserver) {
        window.taskReminderLockObserver = new MutationObserver(syncTaskReminderBodyLock);
        window.taskReminderLockObserver.observe(document.body, { childList: true, subtree: true });
    }

    syncTaskReminderBodyLock();

    const checkTaskReminderEveryThirtyMinutes = () => {
        if (document.querySelector('[role="alertdialog"], [data-task-modal]')) {
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
        const getContext = (field) => {
            const editor = field._quill || field.closest('[data-task-editor]')?._quill;
            const selection = editor ? editor.getSelection() : null;

            if (editor && !selection) {
                return null;
            }

            const end = editor ? selection.index : field.selectionStart;
            const beforeCursor = editor
                ? editor.getText(0, end)
                : field.value.slice(0, end);
            const match = beforeCursor.match(/(?:^|\s)([/@])([^\s]*)$/);

            if (!match) {
                return null;
            }

            return {
                trigger: match[1],
                query: match[2].toLowerCase(),
                start: beforeCursor.length - match[0].length + (match[0][0] === ' ' ? 1 : 0),
                end,
            };
        };

        const closeSuggestions = (container) => {
            const suggestions = container.querySelector('[data-task-suggestions]');

            if (suggestions) {
                suggestions.classList.add('hidden');
                suggestions.innerHTML = '';
            }
        };

        const showSuggestions = (field) => {
            const container = field.closest('[data-task-mentions]');
            const suggestions = container?.querySelector('[data-task-suggestions]');
            const context = getContext(field);

            if (!container || !suggestions || !context) {
                if (container) {
                    closeSuggestions(container);
                }

                return;
            }

            container._taskMentionContext = context;
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

            suggestions.replaceChildren();

            matches.forEach((item) => {
                const button = document.createElement('button');
                const trigger = document.createElement('span');

                button.type = 'button';
                button.dataset.taskSuggestion = item.name;
                button.className = 'block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100';
                trigger.className = 'mr-2 font-semibold text-indigo-600';
                trigger.textContent = context.trigger;
                button.append(trigger, document.createTextNode(item.name));
                suggestions.append(button);
            });

            suggestions.classList.remove('hidden');
        };

        const initializeTaskEditors = (root = document) => {
            if (!window.Quill) {
                return;
            }

            const containers = [];

            if (root.matches?.('[data-task-editor-container]')) {
                containers.push(root);
            }

            containers.push(...root.querySelectorAll?.('[data-task-editor-container]') || []);

            containers.forEach((container) => {
                const editorElement = container.querySelector('[data-task-editor]');

                if (!editorElement || editorElement._quill) {
                    return;
                }

                const editor = new window.Quill(editorElement, {
                    theme: 'snow',
                    placeholder: 'Type / for products or @ for customers',
                    modules: {
                        toolbar: [
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ list: 'ordered' }, { list: 'bullet' }],
                            ['link', 'clean'],
                        ],
                    },
                });
                const initialContent = container.dataset.initialContent || '';

                editor.clipboard.dangerouslyPasteHTML(initialContent || '<p><br></p>');
                editorElement._quill = editor;
                editorElement._taskEditorInitialContent = initialContent;
                editorElement.hidden = false;
                editor.on('text-change', () => {
                    const componentRoot = editorElement.closest('[wire\\:id]');
                    const wire = componentRoot && Livewire.find(componentRoot.getAttribute('wire:id'));
                    const property = container.dataset.livewireProperty;
                    const content = editor.getText().trim() ? editor.root.innerHTML : '';

                    editorElement._taskEditorInitialContent = content;
                    wire?.$set(property, content, false);
                    window.requestAnimationFrame(() => showSuggestions(editor.root));
                });
                editor.root.addEventListener('keyup', () => showSuggestions(editor.root));
                editor.root.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        closeSuggestions(editor.root.closest('[data-task-mentions]'));
                    }
                });
            });
        };

        const syncTaskEditors = (root) => {
            initializeTaskEditors(root);

            const containers = [];

            if (root.matches?.('[data-task-editor-container]')) {
                containers.push(root);
            }

            containers.push(...root.querySelectorAll?.('[data-task-editor-container]') || []);

            containers.forEach((container) => {
                const editorElement = container.querySelector('[data-task-editor]');
                const editor = editorElement?._quill;
                const initialContent = container.dataset.initialContent || '';

                if (editor && initialContent !== editorElement._taskEditorInitialContent) {
                    editor.clipboard.dangerouslyPasteHTML(initialContent || '<p><br></p>');
                    editorElement._taskEditorInitialContent = initialContent;
                }
            });
        };

        document.addEventListener('livewire:init', () => {
            initializeTaskEditors();
            Livewire.hook('morph.added', ({ el }) => initializeTaskEditors(el));
            Livewire.hook('morph.updated', ({ el }) => syncTaskEditors(el));
        }, { once: true });
        window.addEventListener('load', () => initializeTaskEditors(), { once: true });
        document.addEventListener('input', (event) => {
            if (event.target.matches('[data-task-mentions] input')) {
                showSuggestions(event.target);
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && event.target.matches('[data-task-mentions] input')) {
                closeSuggestions(event.target.closest('[data-task-mentions]'));
            }
        });

        document.addEventListener('click', (event) => {
            const suggestion = event.target.closest('[data-task-suggestion]');

            if (!suggestion) {
                return;
            }

            const container = suggestion.closest('[data-task-mentions]');
            const editorElement = container?.querySelector('[data-task-editor]');
            const field = container?.querySelector('input, textarea');
            const editor = editorElement?._quill;
            const context = container?._taskMentionContext;

            if ((!field && !editor) || !context) {
                return;
            }

            const name = suggestion.dataset.taskSuggestion;

            if (editor) {
                editor.deleteText(context.start, context.end - context.start, 'user');
                editor.insertText(context.start, `${context.trigger}${name} `, 'user');
                editor.setSelection(context.start + context.trigger.length + name.length + 1, 0, 'user');
                editor.focus();
            } else {
                field.setRangeText(`${context.trigger}${name} `, context.start, context.end, 'end');
                field.dispatchEvent(new Event('input', { bubbles: true }));
                field.focus();
            }

            closeSuggestions(container);
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
</div>
