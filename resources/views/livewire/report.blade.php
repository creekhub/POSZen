<div class="min-h-screen bg-slate-100 p-6 text-slate-800">
    <div class="mx-auto max-w-7xl">
        @include('livewire.partials.navigation')

        <header class="mb-8 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Business overview</p>
            <h1 class="mt-1 text-3xl font-bold">Sales Report</h1>
        </header>

        <section class="mb-8 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <form wire:submit="applyFilters" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                <div>
                    <label for="report-start-date" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">From</label>
                    <input id="report-start-date" type="date" wire:model="startDate" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                    @error('startDate') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="report-end-date" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">To</label>
                    <input id="report-end-date" type="date" wire:model="endDate" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                    @error('endDate') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-700">Run report</button>
            </form>
        </section>

        <section class="mb-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-4" aria-label="Sales summary">
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm text-slate-500">Sales</p>
                <p class="mt-3 text-2xl font-bold">₱{{ number_format($salesTotal, 2) }}</p>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm text-slate-500">Transactions</p>
                <p class="mt-3 text-2xl font-bold">{{ number_format($transactionCount) }}</p>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm text-slate-500">Average sale</p>
                <p class="mt-3 text-2xl font-bold">₱{{ number_format($averageSale, 2) }}</p>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm text-slate-500">Units sold</p>
                <p class="mt-3 text-2xl font-bold">{{ rtrim(rtrim(number_format($unitsSold, 2), '0'), '.') }}</p>
            </div>
        </section>

        <section class="mb-8 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-xl font-bold">Top products</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-120 text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-3 font-semibold">Code</th>
                            <th class="px-3 py-3 font-semibold">Product</th>
                            <th class="px-3 py-3 text-right font-semibold">Units sold</th>
                            <th class="px-3 py-3 text-right font-semibold">Sales</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($topProducts as $product)
                            <tr>
                                <td class="px-3 py-3 text-slate-500">{{ $product->Code ?: '-' }}</td>
                                <td class="px-3 py-3 font-medium">{{ $product->Name }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ rtrim(rtrim(number_format((float) $product->quantity_sold, 2), '0'), '.') }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">₱{{ number_format((float) $product->sales_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-8 text-center text-slate-500">No product sales for this date range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-xl font-bold">Transactions</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-120 text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-3 font-semibold">Date</th>
                            <th class="px-3 py-3 font-semibold">Transaction</th>
                            <th class="px-3 py-3 font-semibold">Customer</th>
                            <th class="px-3 py-3 text-right font-semibold">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($sales as $sale)
                            <tr>
                                <td class="px-3 py-3 text-slate-600">{{ \Carbon\Carbon::parse($sale->DateCreated)->format('M j, Y g:i A') }}</td>
                                <td class="px-3 py-3 font-medium">{{ $sale->Number }}</td>
                                <td class="px-3 py-3">{{ $sale->customer_name ?: 'Walk-in customer' }}</td>
                                <td class="px-3 py-3 text-right font-semibold tabular-nums">₱{{ number_format((float) $sale->Total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-8 text-center text-slate-500">No transactions for this date range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $sales->links() }}</div>
        </section>

    </div>
</div>

