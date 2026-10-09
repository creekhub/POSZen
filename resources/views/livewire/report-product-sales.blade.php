<div class="min-h-screen bg-slate-100 p-6 text-slate-800">
    <div class="mx-auto max-w-7xl">
        @include('livewire.partials.navigation')

        <header class="mb-8 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Reports</p>
            <h1 class="mt-1 text-3xl font-bold">Product Sales</h1>
        </header>

        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div>
                <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Product performance</p>
                <h2 class="mt-1 text-2xl font-bold">Product sales report</h2>
                <p class="mt-1 text-sm text-slate-500">Search for one or more products and set a date range.</p>
            </div>

            <div class="mt-5 grid gap-3 lg:grid-cols-[minmax(16rem,2fr)_minmax(10rem,1fr)_minmax(10rem,1fr)_auto] lg:items-end">
                <div>
                    <label for="product-report-search" class="mb-2 block text-sm font-medium text-slate-600">Products</label>
                    <input id="product-report-search" type="search" wire:model.live.debounce.250ms="productSearch" autocomplete="off" placeholder="Search by product name or code..." class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label for="product-report-start" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">From</label>
                    <input id="product-report-start" type="date" wire:model="productStartDate" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label for="product-report-end" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">To</label>
                    <input id="product-report-end" type="date" wire:model="productEndDate" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                </div>
                <button type="button" wire:click="searchProducts" wire:loading.attr="disabled" wire:target="searchProducts" class="rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:cursor-wait disabled:opacity-60">
                    <span wire:loading.remove wire:target="searchProducts">Search</span>
                    <span wire:loading wire:target="searchProducts" role="status">Loading...</span>
                </button>
            </div>

            @error('productStartDate') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
            @error('productEndDate') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
            @error('selectedProductIds.*') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror

            @if ($productSuggestions !== [])
                <div class="mt-4 max-h-48 overflow-y-auto rounded-xl border border-slate-200 bg-white">
                    @foreach ($productSuggestions as $productSuggestion)
                        <button type="button" wire:key="product-report-suggestion-{{ $productSuggestion['Id'] }}" wire:click="addProductToSearch({{ $productSuggestion['Id'] }})" class="flex w-full items-center gap-3 border-b border-slate-100 px-3 py-2 text-left last:border-b-0 hover:bg-slate-50">
                            <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-700">{{ $productSuggestion['Name'] }}</span>
                            <span class="text-xs text-slate-400">{{ $productSuggestion['Code'] }}</span>
                            <span class="text-xs font-semibold text-indigo-700">Add</span>
                        </button>
                    @endforeach
                </div>
            @elseif (mb_strlen(trim($productSearch)) >= 2)
                <p class="mt-3 text-sm text-slate-500">No matching products found.</p>
            @endif

            @if ($selectedProductNames !== [])
                <div class="mt-4 flex flex-wrap gap-2" aria-label="Selected products">
                    @foreach ($selectedProductNames as $selectedProduct)
                        <span wire:key="selected-report-product-{{ $selectedProduct['Id'] }}" class="inline-flex items-center gap-2 rounded-lg bg-slate-100 py-1 pl-3 pr-1 text-sm font-medium text-slate-700">
                            {{ $selectedProduct['Name'] }}
                            <button type="button" wire:click="removeProductFromSearch({{ $selectedProduct['Id'] }})" aria-label="Remove {{ $selectedProduct['Name'] }}" class="rounded-md px-2 py-1 text-slate-500 hover:bg-slate-200 hover:text-slate-900">&times;</button>
                        </span>
                    @endforeach
                </div>
            @endif

            <p wire:loading wire:target="searchProducts" role="status" aria-live="polite" class="mt-6 rounded-lg bg-indigo-50 px-4 py-3 text-sm font-medium text-indigo-800">
                Updating Product details, Sales trend, and Profit trend...
            </p>

            <div wire:loading.class="opacity-60" wire:target="searchProducts" class="mt-6 space-y-8 transition-opacity" aria-live="polite">
                <div class="min-w-0">
                    <h3 class="text-lg font-bold">Product details</h3>
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full min-w-155 text-left text-sm">
                            <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="py-3 pr-3 font-semibold">Product name</th>
                                    <th class="px-3 py-3 text-right font-semibold">Total quantity</th>
                                    <th class="px-3 py-3 text-right font-semibold">Total amount</th>
                                    <th class="px-3 py-3 text-right font-semibold">Total profit</th>
                                    <th class="py-3 pl-3 text-right font-semibold">Total discount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($productReport['products'] as $product)
                                    <tr>
                                        <td class="py-3 pr-3 font-medium text-slate-800">{{ $product['product_name'] }}</td>
                                        <td class="px-3 py-3 text-right tabular-nums">{{ rtrim(rtrim(number_format((float) $product['total_quantity'], 2), '0'), '.') }}</td>
                                        <td class="px-3 py-3 text-right tabular-nums">₱{{ number_format((float) $product['total_amount'], 2) }}</td>
                                        <td class="px-3 py-3 text-right tabular-nums">₱{{ number_format((float) $product['total_profit'], 2) }}</td>
                                        <td class="py-3 pl-3 text-right tabular-nums">₱{{ number_format((float) $product['total_discount'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-8 text-center text-sm text-slate-500">{{ empty($productReport['selectedProducts']) ? 'Search for products to view their sales.' : 'No sales found for these products and dates.' }}</td></tr>
                                @endforelse
                            </tbody>
                            @if (!empty($productReport['products']))
                                <tfoot class="border-t border-slate-200 font-semibold text-slate-800">
                                    <tr>
                                        <td class="py-3 pr-3">Selected total</td>
                                        <td class="px-3 py-3 text-right tabular-nums">{{ rtrim(rtrim(number_format((float) $productReport['totalQuantity'], 2), '0'), '.') }}</td>
                                        <td class="px-3 py-3 text-right tabular-nums">₱{{ number_format((float) $productReport['totalAmount'], 2) }}</td>
                                        <td class="px-3 py-3 text-right tabular-nums">₱{{ number_format((float) $productReport['totalProfit'], 2) }}</td>
                                        <td class="py-3 pl-3 text-right tabular-nums">₱{{ number_format((float) $productReport['totalDiscount'], 2) }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>

                <div class="min-w-0">
                    <div class="flex items-baseline justify-between gap-3">
                        <h3 class="text-lg font-bold">Sales trend</h3>
                        <span class="text-xs text-slate-500">{{ rtrim(rtrim(number_format((float) $productReport['totalQuantity'], 2), '0'), '.') }} units total</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Daily quantity by product, {{ \Carbon\Carbon::parse($productReport['startDate'])->format('M j, Y') }} to {{ \Carbon\Carbon::parse($productReport['endDate'])->format('M j, Y') }}</p>
                    <div data-trend-scroll wire:ignore class="mt-3 w-full overflow-x-auto pb-2">
                        <div data-trend-chart-content class="relative h-64 min-w-full">
                            <canvas id="product-sales-trend" class="block h-full w-full" role="img" aria-label="Daily quantity trend for each selected product"></canvas>
                        </div>
                    </div>
                </div>

                <div class="min-w-0 border-t border-slate-200 pt-6">
                    <div class="flex items-baseline justify-between gap-3">
                        <h3 class="text-lg font-bold">Profit trend</h3>
                        <span id="product-profit-total" class="text-xs font-semibold text-emerald-700">₱{{ number_format((float) $productReport['profitTotal'], 2) }}</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Daily total profit across all products, {{ \Carbon\Carbon::parse($productReport['startDate'])->format('M j, Y') }} to {{ \Carbon\Carbon::parse($productReport['endDate'])->format('M j, Y') }}</p>
                    <div data-trend-scroll wire:ignore class="mt-3 w-full overflow-x-auto pb-2">
                        <div data-trend-chart-content class="relative h-64 min-w-full">
                            <canvas id="product-profit-trend" class="block h-full w-full" role="img" aria-label="Daily total profit across all products in the selected date range"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
