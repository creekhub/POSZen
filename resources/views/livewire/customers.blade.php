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

            @if ($actionMessage)
                <output class="mb-4 block rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ $actionMessage }}</output>
            @endif
            @error('customer') <p class="mb-4 text-sm text-rose-600">{{ $message }}</p> @enderror

            <div class="overflow-x-auto">
                <table class="w-full min-w-240 text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-3 font-semibold">Code</th>
                            <th class="px-3 py-3 font-semibold">Name</th>
                            <th class="px-3 py-3 font-semibold">Email</th>
                            <th class="px-3 py-3 font-semibold">Phone</th>
                            <th class="px-3 py-3 font-semibold">City</th>
                            <th class="px-3 py-3 font-semibold">Status</th>
                            <th class="px-3 py-3 text-right font-semibold">Outstanding</th>
                            <th class="px-3 py-3 text-right font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($customers as $customer)
                            <tr wire:key="customer-{{ $customer->Id }}">
                                <td class="px-3 py-3 text-slate-500">{{ $customer->Code ?: '-' }}</td>
                                <td class="px-3 py-3 font-medium text-slate-800">{{ $customer->Name }}</td>
                                <td class="px-3 py-3">{{ $customer->Email ?: '-' }}</td>
                                <td class="px-3 py-3">{{ $customer->PhoneNumber ?: '-' }}</td>
                                <td class="px-3 py-3">{{ $customer->City ?: '-' }}</td>
                                <td class="px-3 py-3 {{ $customer->IsEnabled ? 'text-emerald-700' : 'text-slate-500' }}">{{ $customer->IsEnabled ? 'Enabled' : 'Disabled' }}</td>
                                <td class="px-3 py-3 text-right tabular-nums {{ $customer->outstanding_balance > 0 ? 'font-semibold text-rose-700' : 'text-slate-500' }}">₱{{ number_format($customer->outstanding_balance, 2) }}</td>
                                <td class="px-3 py-3">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" wire:click="editCustomer({{ $customer->Id }})" aria-label="Edit {{ $customer->Name }}" title="Edit customer" class="inline-flex size-9 items-center justify-center rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100">
                                            <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" wire:click="viewStatement({{ $customer->Id }})" aria-label="View statement of account for {{ $customer->Name }}" title="View SOA" class="inline-flex size-9 items-center justify-center rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100">
                                            <i class="fa-solid fa-file-invoice" aria-hidden="true"></i>
                                        </button>
                                        @if ($customer->outstanding_balance > 0)
                                            <button type="button" wire:click="startCustomerPayment({{ $customer->Id }})" aria-label="Receive payment from {{ $customer->Name }}" title="Receive payment" class="inline-flex size-9 items-center justify-center rounded-lg bg-emerald-700 text-white hover:bg-emerald-800">
                                                <i class="fa-solid fa-credit-card" aria-hidden="true"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-3 py-10 text-center text-slate-500">No customers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $customers->links(data: ['scrollTo' => false]) }}</div>
        </section>

        @if ($editingCustomerId)
            <div class="fixed inset-0 z-40 flex items-center justify-center overflow-y-auto bg-slate-950/60 p-4">
                <dialog open class="static m-0 w-full max-w-2xl rounded-xl border-0 bg-white p-6 shadow-2xl" aria-modal="true" aria-labelledby="edit-customer-title">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm uppercase tracking-[0.15em] text-slate-500">Customer record</p>
                            <h2 id="edit-customer-title" class="mt-1 text-xl font-bold">Edit customer</h2>
                        </div>
                        <button type="button" wire:click="$set('editingCustomerId', null)" aria-label="Close edit customer" class="rounded-lg border border-slate-300 px-3 py-2 text-slate-600 hover:bg-slate-100"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                    </div>

                    <form wire:submit="saveCustomer" class="mt-5 space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="edit-customer-name" class="mb-1 block text-sm font-medium text-slate-600">Name</label>
                                <input id="edit-customer-name" wire:model="editName" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                                @error('editName') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="edit-customer-code" class="mb-1 block text-sm font-medium text-slate-600">Code</label>
                                <input id="edit-customer-code" wire:model="editCode" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                                @error('editCode') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="edit-customer-city" class="mb-1 block text-sm font-medium text-slate-600">City</label>
                                <input id="edit-customer-city" wire:model="editCity" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                                @error('editCity') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="edit-customer-email" class="mb-1 block text-sm font-medium text-slate-600">Email</label>
                                <input id="edit-customer-email" wire:model="editEmail" type="email" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                                @error('editEmail') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="edit-customer-phone" class="mb-1 block text-sm font-medium text-slate-600">Phone</label>
                                <input id="edit-customer-phone" wire:model="editPhoneNumber" type="tel" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                                @error('editPhoneNumber') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label for="edit-customer-address" class="mb-1 block text-sm font-medium text-slate-600">Address</label>
                                <input id="edit-customer-address" wire:model="editAddress" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                                @error('editAddress') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                            <input wire:model="editIsEnabled" type="checkbox" class="size-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                            Enabled
                        </label>
                        <div class="flex justify-end gap-2 border-t border-slate-200 pt-4">
                            <button type="button" wire:click="$set('editingCustomerId', null)" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Cancel</button>
                            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Save changes</button>
                        </div>
                    </form>
                </dialog>
            </div>
        @endif

        @if ($selectedCustomerId && $soaCustomer)
            <div class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-slate-950/60 p-4">
                <dialog id="state-of-account" open class="customer-soa-dialog static my-0 min-w-0 max-w-none rounded-xl border-0 bg-white p-5 shadow-2xl sm:my-4" aria-modal="true" aria-labelledby="customer-soa-title">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm uppercase tracking-[0.15em] text-slate-500">Receivables</p>
                            <h2 id="customer-soa-title" class="mt-1 text-xl font-bold">State of Account</h2>
                            <p id="soa-print-customer" class="mt-1 text-sm text-slate-600">Customer: {{ $soaCustomer->Name }}</p>
                        </div>
                        <button type="button" wire:click="closeStatement" aria-label="Close customer statement" class="rounded-lg border border-slate-300 px-3 py-2 text-slate-600 hover:bg-slate-100"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                    </div>

                    @if ($actionMessage)
                        <output class="mt-4 block rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ $actionMessage }}</output>
                    @endif

                    <div id="soa-summary" class="mt-5 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-lg bg-slate-100 p-4">
                            <p class="text-sm text-slate-600">Grand Total</p>
                            <p class="mt-1 text-xl font-bold text-slate-950">₱{{ number_format($unpaidSales->sum('Total'), 2) }}</p>
                        </div>
                        <div class="rounded-lg bg-emerald-50 p-4">
                            <p class="text-sm text-emerald-700">Total Payment</p>
                            <p class="mt-1 text-xl font-bold text-emerald-950">₱{{ number_format($unpaidSales->sum('paid_amount'), 2) }}</p>
                        </div>
                        <div class="rounded-lg bg-rose-50 p-4">
                            <p class="text-sm text-rose-700">Outstanding Balance</p>
                            <p class="mt-1 text-xl font-bold text-rose-950">₱{{ number_format($unpaidSales->sum('balance'), 2) }}</p>
                        </div>
                    </div>

                    <div class="soa-table-scroll mt-5 overflow-x-auto">
                        <table id="soa-table" class="w-full min-w-180 text-left text-sm">
                            <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-3 py-3 font-semibold"><span class="soa-screen-header">Document</span><span class="soa-print-header">#</span></th>
                                    <th class="px-3 py-3 font-semibold"><span class="soa-screen-header">Sale Date</span><span class="soa-print-header">Date</span></th>
                                    <th class="px-3 py-3 text-right font-semibold">Payment</th>
                                    <th class="px-3 py-3 text-right font-semibold"><span class="soa-screen-header">Amount Due</span><span class="soa-print-header">Due</span></th>
                                    <th class="soa-action-column px-3 py-3 text-right font-semibold">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($unpaidSales as $sale)
                                    <tr wire:key="soa-sale-{{ $sale->Id }}">
                                        <td class="px-3 py-3 font-semibold">{{ $sale->Number }}</td>
                                        <td class="px-3 py-3 text-slate-600">{{ \Carbon\Carbon::parse($sale->Date)->format('M j, Y') }}</td>
                                        <td class="px-3 py-3 text-right tabular-nums text-emerald-700">₱{{ number_format($sale->paid_amount, 2) }}</td>
                                        <td class="px-3 py-3 text-right font-semibold tabular-nums text-rose-700">₱{{ number_format($sale->balance, 2) }}</td>
                                        <td class="soa-action-column px-3 py-3 text-right">
                                            @if ($sale->balance > 0)
                                                <button type="button" wire:click="startPayment({{ $sale->Id }})" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-800"><i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i>Receive Payment</button>
                                            @else
                                                <span class="text-xs font-medium text-emerald-700">Paid</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="px-3 pb-4 pt-0">
                                            <div class="ml-3 border-l-2 border-slate-200 pl-3">
                                                @forelse ($sale->items as $item)
                                                    <div class="flex items-center justify-between gap-3 py-1 text-sm text-slate-600">
                                                        <span>{{ $item->product_name }} × {{ rtrim(rtrim(number_format($item->Quantity, 2), '0'), '.') }}</span>
                                                        <span class="text-right">₱{{ number_format($item->Price, 2) }} <span class="font-medium text-slate-800">· ₱{{ number_format($item->Total, 2) }}</span></span>
                                                    </div>
                                                @empty
                                                    <p class="py-2 text-sm text-slate-500">No item details available.</p>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td class="soa-action-column"></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-8 text-center text-slate-500">This customer has no outstanding sales.</td>
                                        <td class="soa-action-column"></td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="border-t-2 border-slate-300">
                                <tr>
                                    <td colspan="3" class="px-3 py-2 text-right text-base font-bold text-slate-800">Grand Total</td>
                                    <td class="px-3 py-2 text-right text-base font-bold text-slate-800">₱{{ number_format($unpaidSales->sum('Total'), 2) }}</td>
                                    <td class="soa-action-column"></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="px-3 py-2 text-right text-base font-bold text-slate-800">Total Payment</td>
                                    <td class="px-3 py-2 text-right text-base font-bold text-emerald-700">₱{{ number_format($unpaidSales->sum('paid_amount'), 2) }}</td>
                                    <td class="soa-action-column"></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="px-3 py-2 text-right text-base font-bold text-slate-800">Outstanding Balance</td>
                                    <td class="px-3 py-2 text-right text-base font-bold text-rose-700">₱{{ number_format($unpaidSales->sum('balance'), 2) }}</td>
                                    <td class="soa-action-column"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"><i class="fa-solid fa-print" aria-hidden="true"></i>Print SOA</button>
                    </div>
                </dialog>
            </div>
        @endif

        @if ($paymentDocumentId)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4">
                <dialog open class="static m-0 w-full max-w-md rounded-xl border-0 bg-white p-5 shadow-2xl" aria-modal="true" aria-labelledby="receive-payment-title">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm uppercase tracking-[0.15em] text-slate-500">Accounts receivable</p>
                            <h2 id="receive-payment-title" class="mt-1 text-xl font-bold">Receive payment</h2>
                        </div>
                        <button type="button" wire:click="cancelPayment" aria-label="Close receive payment" class="rounded-lg border border-slate-300 px-3 py-2 text-slate-600 hover:bg-slate-100"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                    </div>

                    <form wire:submit="recordPayment" class="mt-5 space-y-4">
                        <div>
                            <label for="payment-type" class="mb-1 block text-sm font-medium text-slate-600">Payment method</label>
                            <select id="payment-type" wire:model="paymentTypeId" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500">
                                @foreach ($paymentTypes as $paymentType)
                                    <option value="{{ $paymentType->Id }}">{{ $paymentType->Name }}</option>
                                @endforeach
                            </select>
                            @error('paymentTypeId') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="payment-amount" class="mb-1 block text-sm font-medium text-slate-600">Amount</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-sm text-slate-500">₱</span>
                                <input id="payment-amount" wire:model="paymentAmount" type="number" min="0.01" step="0.01" required class="w-full rounded-lg border border-slate-300 py-2 pl-8 pr-3 text-sm outline-none focus:border-indigo-500">
                            </div>
                            @error('paymentAmount') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex justify-end gap-2 border-t border-slate-200 pt-4">
                            <button type="button" wire:click="cancelPayment" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Cancel</button>
                            <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Record payment</button>
                        </div>
                    </form>
                </dialog>
            </div>
        @endif
    </div>

    <style>
        .customer-soa-dialog {
            width: min(90rem, calc(100vw - 2rem));
            min-width: min(42rem, calc(100vw - 2rem));
            height: min(85dvh, 58rem);
            min-height: min(34rem, calc(100dvh - 2rem));
            max-height: calc(100dvh - 2rem);
            overflow-y: auto;
        }

    </style>

    @include('livewire.partials.soa-print-styles')
</div>
