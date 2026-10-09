<div class="min-h-screen bg-slate-100 p-6 text-slate-800">
    <div class="mx-auto max-w-7xl">
        @include('livewire.partials.navigation')

        <header class="mb-8 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Reports</p>
            <h1 class="mt-1 text-3xl font-bold">Sales Trend</h1>
        </header>

        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
                <div>
                    <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Sales activity</p>
                    <h2 class="mt-1 text-2xl font-bold">{{ $chartPeriod }} sales</h2>
                    <h3 class="mt-1 text-lg">₱{{ number_format($salesAverage, 2) }} Average {{ $averageLabel }}</h3>
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
                            <div class="w-full max-w-10 rounded-t-md bg-emerald-600 transition-colors group-hover:bg-emerald-800" style="height: {{ $point['height'] }}%"></div>
                            <span class="whitespace-nowrap text-[10px] text-slate-500">{{ $point['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
</div>

