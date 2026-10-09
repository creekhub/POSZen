<div class="min-h-screen bg-slate-100 p-6 text-slate-800">
    <div class="mx-auto max-w-7xl">
        @include('livewire.partials.navigation')

        <header class="mb-8 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Directory</p>
            <h1 class="mt-1 text-3xl font-bold">Customers</h1>
        </header>

        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <label for="customer-search" class="mb-2 block text-sm font-medium text-slate-600">Search customers</label>
            <input id="customer-search" type="search" wire:model.live.debounce.250ms="search" placeholder="Name, code, email, or phone" class="mb-5 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-indigo-500 sm:max-w-lg">

            <div class="overflow-x-auto">
                <table class="w-full min-w-160 text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-3 font-semibold">Code</th>
                            <th class="px-3 py-3 font-semibold">Name</th>
                            <th class="px-3 py-3 font-semibold">Email</th>
                            <th class="px-3 py-3 font-semibold">Phone</th>
                            <th class="px-3 py-3 font-semibold">City</th>
                            <th class="px-3 py-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($customers as $customer)
                            <tr>
                                <td class="px-3 py-3 text-slate-500">{{ $customer->Code ?: '-' }}</td>
                                <td class="px-3 py-3 font-medium text-slate-800">{{ $customer->Name }}</td>
                                <td class="px-3 py-3">{{ $customer->Email ?: '-' }}</td>
                                <td class="px-3 py-3">{{ $customer->PhoneNumber ?: '-' }}</td>
                                <td class="px-3 py-3">{{ $customer->City ?: '-' }}</td>
                                <td class="px-3 py-3 {{ $customer->IsEnabled ? 'text-emerald-700' : 'text-slate-500' }}">{{ $customer->IsEnabled ? 'Enabled' : 'Disabled' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-10 text-center text-slate-500">No customers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $customers->links() }}</div>
        </section>
    </div>
</div>
