<div class="min-h-screen bg-slate-100 p-6 text-slate-800">
    <div class="mx-auto max-w-7xl">
        @include('livewire.partials.navigation')

        <header class="mb-8 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Catalog</p>
            <h1 class="mt-1 text-3xl font-bold">Products</h1>
        </header>

        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <label for="product-search" class="mb-2 block text-sm font-medium text-slate-600">Search products</label>
            <input id="product-search" type="search" wire:model.live.debounce.250ms="search" placeholder="Product name or code" class="mb-5 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-indigo-500 sm:max-w-lg">

            <div class="overflow-x-auto">
                <table class="w-full min-w-120 text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-3 font-semibold">Code</th>
                            <th class="px-3 py-3 font-semibold">Product</th>
                            <th class="px-3 py-3 text-right font-semibold">Price</th>
                            <th class="px-3 py-3 font-semibold">Unit</th>
                            <th class="px-3 py-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($products as $product)
                            <tr>
                                <td class="px-3 py-3 text-slate-500">{{ $product->Code ?: '-' }}</td>
                                <td class="px-3 py-3 font-medium text-slate-800">{{ $product->Name }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ number_format((float) $product->Price, 2) }}</td>
                                <td class="px-3 py-3">{{ $product->MeasurementUnit ?: '-' }}</td>
                                <td class="px-3 py-3 {{ $product->IsEnabled ? 'text-emerald-700' : 'text-slate-500' }}">{{ $product->IsEnabled ? 'Enabled' : 'Disabled' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-3 py-10 text-center text-slate-500">No products found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $products->links() }}</div>
        </section>
    </div>
</div>
