<div class="min-h-screen bg-slate-100 p-6 text-slate-800">
    <div class="mx-auto max-w-7xl">
        @include('livewire.partials.navigation')

        <header class="mb-8 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Reports</p>
            <h1 class="mt-1 text-3xl font-bold">Favorite Products</h1>
        </header>

        <section class="mb-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(18rem,0.8fr)]">
                <div>
                    <h2 class="text-lg font-bold">Tracked products</h2>
                    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div class="min-w-0 flex-1">
                            <label for="favorite-product-search" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Find a product</label>
                            <div wire:ignore>
                                <select id="favorite-product-search" data-search-url="{{ route('reports.favorite-products.product-options') }}" multiple class="w-full"></select>
                            </div>
                        </div>
                        <button type="button" wire:click="addProducts" class="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Add selected</button>
                    </div>
                    @error('selectedProductIds') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror

                    <ul class="mt-5 divide-y divide-slate-200 border-y border-slate-200">
                        @forelse ($favoriteProducts as $product)
                            <li wire:key="favorite-product-{{ $product->Id }}" class="flex flex-wrap items-center justify-between gap-3 py-3">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium">{{ $product->Name }}</p>
                                    <p class="text-sm text-slate-500">{{ $product->Code ?: 'No product code' }}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-3 text-right text-xs sm:min-w-52">
                                    <div>
                                        <p class="text-slate-500">Total sales</p>
                                        <p class="font-semibold tabular-nums">₱{{ number_format($product->totalSales, 2) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-slate-500">Total profit</p>
                                        <p class="font-semibold tabular-nums {{ $product->totalProfit < 0 ? 'text-rose-700' : 'text-emerald-700' }}">₱{{ number_format($product->totalProfit, 2) }}</p>
                                    </div>
                                </div>
                                <button type="button" wire:click="removeProduct({{ $product->Id }})" aria-label="Remove {{ $product->Name }}" class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg border border-slate-300 text-slate-600 hover:border-rose-300 hover:text-rose-700">
                                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                </button>
                            </li>
                        @empty
                            <li class="py-6 text-sm text-slate-500">No products are being tracked yet.</li>
                        @endforelse
                    </ul>
                </div>

                <div class="flex flex-col gap-4 sm:flex-row lg:flex-col lg:justify-start">
                    <div>
                        <label for="favorite-start-date" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">From</label>
                        <input id="favorite-start-date" type="date" wire:model.live="startDate" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-emerald-600">
                    </div>
                    <div>
                        <label for="favorite-end-date" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">To</label>
                        <input id="favorite-end-date" type="date" wire:model.live="endDate" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-emerald-600">
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold">Sales and profit trend</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ \Carbon\Carbon::parse($startDate)->format('M j, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('M j, Y') }}</p>
                </div>
                <div class="flex gap-5 text-sm">
                    <p><span class="text-slate-500">Total sales</span><br><span class="font-semibold text-emerald-700">₱{{ number_format($chart['totalSales'], 2) }}</span></p>
                    <p><span class="text-slate-500">Total profit</span><br><span class="font-semibold text-amber-700">₱{{ number_format($chart['totalProfit'], 2) }}</span></p>
                </div>
            </div>
            <div class="mt-6 grid gap-8 xl:grid-cols-2">
                <div class="min-w-0">
                    <h3 class="font-semibold">Total Sales</h3>
                    <div class="mt-3 overflow-x-auto pb-2">
                        <div class="relative h-80 min-w-160">
                            <canvas id="favorite-products-sales-chart" data-labels="{{ json_encode($chart['labels']) }}" data-datasets="{{ json_encode($chart['datasets']) }}" wire:ignore aria-label="Favorite products total sales trend"></canvas>
                        </div>
                    </div>
                </div>
                <div class="min-w-0">
                    <h3 class="font-semibold">Total Profit</h3>
                    <div class="mt-3 overflow-x-auto pb-2">
                        <div class="relative h-80 min-w-160">
                            <canvas id="favorite-products-profit-chart" data-labels="{{ json_encode($chart['labels']) }}" data-datasets="{{ json_encode($chart['datasets']) }}" wire:ignore aria-label="Favorite products total profit trend"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
