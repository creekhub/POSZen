<div class="min-h-screen bg-slate-100 p-6 text-slate-800">
    <div class="mx-auto max-w-7xl">
        @include('livewire.partials.navigation')

        <header class="mb-8 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Receivables</p>
            <h1 class="mt-1 text-3xl font-bold">State of Account</h1>
        </header>

        <section id="state-of-account" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <p class="text-sm text-slate-500">Unpaid sales for the selected customer.</p>
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
                    <button type="button" onclick="window.print()" class="self-end rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Print SOA</button>
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
                            <tr><td colspan="4" class="px-3 py-8 text-center text-slate-500">This customer has no unpaid sales.</td></tr>
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

    @include('livewire.partials.soa-print-styles')
</div>
