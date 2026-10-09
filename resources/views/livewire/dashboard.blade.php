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
                                    <div>
                                        <label for="edit-task-title-{{ $task->Id }}" class="mb-1 block text-xs font-medium text-slate-600">Title</label>
                                        <input id="edit-task-title-{{ $task->Id }}" wire:model="editTaskTitle" type="text" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500">
                                    </div>
                                    @error('editTaskTitle') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                                    <div>
                                        <label for="edit-task-notes-{{ $task->Id }}" class="mb-1 block text-xs font-medium text-slate-600">Notes</label>
                                        <textarea id="edit-task-notes-{{ $task->Id }}" wire:model="editTaskDescription" rows="2" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500"></textarea>
                                    </div>
                                    @error('editTaskDescription') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                                    <div>
                                        <label for="edit-task-due-date-{{ $task->Id }}" class="mb-1 block text-xs font-medium text-slate-600">Due date</label>
                                        <input id="edit-task-due-date-{{ $task->Id }}" wire:model="editTaskDueDate" type="date" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500">
                                    </div>
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
                                        <p class="mt-2 whitespace-pre-line wrap-break-word text-sm text-slate-600">{{ $task->Description }}</p>
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
    </div>

<script>
    window.taskMentionData = {
        products: @json($taskProducts->map(fn ($product) => ['name' => $product->Name])->values()),
        customers: @json($taskCustomers->map(fn ($customer) => ['name' => $customer->Name])->values()),
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
</div>
