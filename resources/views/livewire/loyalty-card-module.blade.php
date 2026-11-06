<div class="min-h-screen bg-slate-100 p-6 text-slate-800">
    <div class="mx-auto max-w-7xl">
        @include('livewire.partials.navigation')

        <header class="mb-6 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Customer rewards</p>
            <h1 class="mt-1 text-3xl font-bold">Loyalty Card</h1>
        </header>

        <nav aria-label="Loyalty card sections" class="mb-6 flex flex-wrap gap-2 border-b border-slate-300">
            @foreach ([['overview', 'Overview'], ['cards', 'Cards'], ['rules', 'Rules'], ['transactions', 'Transactions']] as [$tab, $label])
                <button type="button" wire:click="setTab('{{ $tab }}')" @if ($activeTab === $tab) aria-current="page" @endif @class([
                    'border-b-2 px-4 py-3 text-sm font-semibold',
                    'border-emerald-700 text-emerald-800' => $activeTab === $tab,
                    'border-transparent text-slate-600 hover:border-slate-400 hover:text-slate-900' => $activeTab !== $tab,
                ])>{{ $label }}</button>
            @endforeach
        </nav>

        @if ($actionMessage)
            <output class="mb-5 block rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ $actionMessage }}</output>
        @endif
        @if ($errors->any())
            <div class="mb-5 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                <p class="font-semibold">Please check the submitted information.</p>
                <ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @if ($activeTab === 'overview')
            <section aria-label="Loyalty program summary" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm text-slate-500">Issued cards</p>
                    <p class="mt-2 text-2xl font-bold tabular-nums">{{ number_format($cardCount) }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm text-slate-500">Active cards</p>
                    <p class="mt-2 text-2xl font-bold tabular-nums text-emerald-700">{{ number_format($activeCardCount) }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm text-slate-500">Earning rules</p>
                    <p class="mt-2 text-2xl font-bold tabular-nums">{{ number_format($ruleCount) }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm text-slate-500">Outstanding points</p>
                    <p class="mt-2 text-2xl font-bold tabular-nums">{{ number_format($pointsBalance, 2) }}</p>
                </div>
            </section>

            <section class="mt-6 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold">Recent activity</h2>
                        <p class="mt-1 text-sm text-slate-500">The latest point changes recorded at checkout.</p>
                    </div>
                    <button type="button" wire:click="setTab('transactions')" class="text-sm font-semibold text-emerald-800 hover:underline">View all transactions</button>
                </div>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-180 text-left text-sm">
                        <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                            <tr><th class="py-3 pr-4 font-semibold">Date</th><th class="px-4 py-3 font-semibold">Customer</th><th class="px-4 py-3 font-semibold">Card</th><th class="px-4 py-3 font-semibold">Document</th><th class="px-4 py-3 text-right font-semibold">Spent</th><th class="py-3 pl-4 text-right font-semibold">Points</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($recentTransactions as $transaction)
                                <tr>
                                    <td class="py-3 pr-4 text-slate-500">{{ \Carbon\Carbon::parse($transaction->CreatedAt)->format('M j, Y g:i A') }}</td>
                                    <td class="px-4 py-3 font-medium">{{ $transaction->CustomerName ?: 'Unknown customer' }}</td>
                                    <td class="px-4 py-3 tabular-nums">{{ $transaction->CardNumber ?: $transaction->LoyaltyCardNumber }}</td>
                                    <td class="px-4 py-3">{{ $transaction->DocumentNumber ?: '-' }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">₱{{ number_format((float) $transaction->AmountSpent, 2) }}</td>
                                    <td class="py-3 pl-4 text-right font-semibold tabular-nums {{ $transaction->PointsChanged < 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ $transaction->PointsChanged > 0 ? '+' : '' }}{{ number_format((float) $transaction->PointsChanged, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-10 text-center text-slate-500">No loyalty transactions have been recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @elseif ($activeTab === 'cards')
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div>
                    <h2 class="text-lg font-bold">Issue a loyalty card</h2>
                    <p class="mt-1 text-sm text-slate-500">Each customer can have one card. Leave the card number blank to generate one.</p>
                </div>
                <form wire:submit="issueCard" class="mt-5 grid gap-4 sm:grid-cols-[minmax(14rem,1fr)_minmax(12rem,1fr)_auto_auto] sm:items-end">
                    <div>
                        <label for="loyalty-customer" class="mb-1 block text-sm font-medium text-slate-700">Customer</label>
                        <select id="loyalty-customer" wire:model="customerId" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-emerald-700 focus:outline-none">
                            <option value="">Select customer</option>
                            @foreach ($availableCustomers as $customer)
                                <option value="{{ $customer->Id }}">{{ $customer->Name }}{{ $customer->Code ? ' · '.$customer->Code : '' }}</option>
                            @endforeach
                        </select>
                        @error('customerId') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="loyalty-card-number" class="mb-1 block text-sm font-medium text-slate-700">EAN-13 card number</label>
                        <input id="loyalty-card-number" wire:model="cardNumber" inputmode="numeric" pattern="4800[0-9]{9}" maxlength="13" placeholder="4800{{ now()->format('Y') }}#####" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-700 focus:outline-none">
                        @error('cardNumber') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                    <button type="button" wire:click="preGenerateCardNumber" wire:loading.attr="disabled" wire:target="preGenerateCardNumber" aria-label="Generate unique EAN-13 card number" title="Generate unique EAN-13 card number" class="inline-flex size-10 items-center justify-center rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100 disabled:opacity-50"><i class="fa-solid fa-barcode" aria-hidden="true"></i></button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="issueCard" @disabled($availableCustomers->isEmpty()) class="rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50">Issue card</button>
                </form>
                @if ($availableCustomers->isEmpty())
                    <p class="mt-3 text-sm text-slate-500">All enabled customers already have a card.</p>
                @endif
                @if ($cardNumber !== '')
                    <div class="mt-5 flex flex-wrap items-center gap-4 border-t border-slate-200 pt-4">
                        <div wire:ignore class="max-w-full overflow-x-auto rounded-lg border border-slate-200 bg-white p-3">
                            <svg id="loyalty-card-barcode" role="img" aria-label="EAN-13 barcode for loyalty card {{ $cardNumber }}"></svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Generated card number</p>
                            <p class="mt-1 font-mono text-lg font-semibold tabular-nums text-slate-900">{{ $cardNumber }}</p>
                        </div>
                    </div>
                @endif
            </section>

            <section class="mt-6 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-bold">Cards</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-180 text-left text-sm">
                        <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                            <tr><th class="py-3 pr-4 font-semibold">Customer</th><th class="px-4 py-3 font-semibold">Card number</th><th class="px-4 py-3 text-right font-semibold">Points balance</th><th class="px-4 py-3 font-semibold">Status</th><th class="py-3 pl-4 text-right font-semibold">Action</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($cards as $card)
                                <tr wire:key="loyalty-card-{{ $card->Id }}">
                                    <td class="py-3 pr-4"><span class="font-medium">{{ $card->CustomerName }}</span>@if ($card->CustomerCode)<span class="ml-2 text-xs text-slate-500">{{ $card->CustomerCode }}</span>@endif</td>
                                    <td class="px-4 py-3 tabular-nums">{{ $card->CardNumber ?: '—' }}</td>
                                    <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ number_format((float) $card->PointsBalance, 2) }}</td>
                                    <td class="px-4 py-3"><span class="inline-flex items-center gap-2"><span class="size-2 rounded-full {{ $card->Status === 'ACTIVE' ? 'bg-emerald-600' : 'bg-slate-400' }}"></span>{{ ucfirst(strtolower((string) $card->Status)) }}</span></td>
                                    <td class="py-3 pl-4 text-right">
                                        <button type="button" wire:click="updateCardStatus({{ $card->Id }}, '{{ $card->Status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE' }}')" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm font-semibold hover:bg-slate-100">{{ $card->Status === 'ACTIVE' ? 'Deactivate' : 'Activate' }}</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-10 text-center text-slate-500">No loyalty cards issued yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @elseif ($activeTab === 'rules')
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                @if ($editingRuleId)
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold">Edit earning rule</h2>
                            <p class="mt-1 text-sm text-slate-500">Awards repeat for each full spend threshold.</p>
                        </div>
                        <button type="button" wire:click="cancelRuleEdit" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold hover:bg-slate-100">Cancel edit</button>
                    </div>
                    <form wire:submit="updateRule" class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
                @else
                    <div>
                        <h2 class="text-lg font-bold">Add earning rule</h2>
                        <p class="mt-1 text-sm text-slate-500">Awards repeat for each full spend threshold. When rules overlap for a payment method, the highest eligible threshold is used.</p>
                    </div>
                    <form wire:submit="createRule" class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
                @endif
                    <div>
                        <label for="loyalty-rule-name" class="mb-1 block text-sm font-medium text-slate-700">Rule name</label>
                        <input id="loyalty-rule-name" wire:model="ruleName" required maxlength="120" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-700 focus:outline-none">
                        @error('ruleName') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="loyalty-payment-type" class="mb-1 block text-sm font-medium text-slate-700">Payment method</label>
                        <select id="loyalty-payment-type" wire:model="paymentTypeId" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-emerald-700 focus:outline-none">
                            <option value="">Select method</option>
                            @foreach ($paymentTypes as $paymentType)
                                <option value="{{ $paymentType->Id }}">{{ $paymentType->Name }}</option>
                            @endforeach
                        </select>
                        @error('paymentTypeId') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="loyalty-spend-threshold" class="mb-1 block text-sm font-medium text-slate-700">Spend threshold (₱)</label>
                        <input id="loyalty-spend-threshold" wire:model="spendThreshold" type="number" min="0.01" step="0.01" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-700 focus:outline-none">
                        @error('spendThreshold') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="loyalty-points-earned" class="mb-1 block text-sm font-medium text-slate-700">Points per threshold</label>
                        <input id="loyalty-points-earned" wire:model="pointsEarned" type="number" min="0.01" step="0.01" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-700 focus:outline-none">
                        @error('pointsEarned') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                    @if ($editingRuleId)
                        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Save rule</button>
                    @else
                        <button type="submit" wire:loading.attr="disabled" wire:target="createRule" @disabled($paymentTypes->isEmpty()) class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50">Add rule</button>
                    @endif
                    </form>
                @if ($paymentTypes->isEmpty())
                    <p class="mt-3 text-sm text-amber-700">No enabled payment methods are available.</p>
                @endif
            </section>

            <section class="mt-6 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-bold">Earning rules</h2>
                <div class="mt-4 divide-y divide-slate-200">
                    @forelse ($rules as $rule)
                        <article wire:key="loyalty-rule-{{ $rule->Id }}" class="flex flex-wrap items-center justify-between gap-4 py-4 first:pt-0 last:pb-0">
                            <div>
                                <h3 class="font-semibold">{{ $rule->RuleName }}</h3>
                                <p class="mt-1 text-sm text-slate-600">{{ $rule->PaymentTypeName ?: 'Unavailable payment method' }} · ₱{{ number_format((float) $rule->SpendThreshold, 2) }} earns {{ number_format((float) $rule->PointsEarned, 2) }} points per full threshold</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" wire:click="editRule({{ $rule->Id }})" class="inline-flex size-9 items-center justify-center rounded-md border border-slate-300 text-slate-700 hover:bg-slate-100" aria-label="Edit {{ $rule->RuleName }}" title="Edit rule"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>
                                <button type="button" wire:click="deleteRule({{ $rule->Id }})" wire:confirm="Delete this earning rule?" class="inline-flex size-9 items-center justify-center rounded-md border border-rose-200 text-rose-700 hover:bg-rose-50" aria-label="Delete {{ $rule->RuleName }}" title="Delete rule"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                            </div>
                        </article>
                    @empty
                        <p class="py-8 text-center text-sm text-slate-500">No earning rules configured yet.</p>
                    @endforelse
                </div>
            </section>
        @else
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div>
                    <h2 class="text-lg font-bold">Transaction history</h2>
                    <p class="mt-1 text-sm text-slate-500">Point awards and redemptions recorded against loyalty cards.</p>
                </div>
                <form wire:submit="applyTransactionFilters" class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(12rem,1fr)_minmax(10rem,1fr)_minmax(10rem,1fr)_auto_auto] lg:items-end">
                    <div>
                        <label for="transaction-card-number" class="mb-1 block text-sm font-medium text-slate-700">Card number</label>
                        <input id="transaction-card-number" wire:model="filterCardNumber" inputmode="numeric" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('filterCardNumber') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="transaction-from" class="mb-1 block text-sm font-medium text-slate-700">From</label>
                        <input id="transaction-from" wire:model="filterFrom" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('filterFrom') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="transaction-to" class="mb-1 block text-sm font-medium text-slate-700">To</label>
                        <input id="transaction-to" wire:model="filterTo" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('filterTo') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-700">Filter</button>
                    <button type="button" wire:click="clearTransactionFilters" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Clear</button>
                </form>
                <div class="mt-5 overflow-x-auto">
                    <table class="w-full min-w-240 text-left text-sm">
                        <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                            <tr><th class="py-3 pr-4 font-semibold">Date</th><th class="px-4 py-3 font-semibold">Customer</th><th class="px-4 py-3 font-semibold">Card</th><th class="px-4 py-3 font-semibold">Document</th><th class="px-4 py-3 text-right font-semibold">Spent</th><th class="px-4 py-3 text-right font-semibold">Points</th><th class="px-4 py-3 font-semibold">Type</th><th class="py-3 pl-4 font-semibold">Notes</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($transactions as $transaction)
                                <tr wire:key="loyalty-transaction-{{ $transaction->Id }}">
                                    <td class="py-3 pr-4 whitespace-nowrap text-slate-500">{{ \Carbon\Carbon::parse($transaction->CreatedAt)->format('M j, Y g:i A') }}</td>
                                    <td class="px-4 py-3 font-medium">{{ $transaction->CustomerName ?: 'Unknown customer' }}</td>
                                    <td class="px-4 py-3 tabular-nums">{{ $transaction->CardNumber ?: $transaction->LoyaltyCardNumber }}</td>
                                    <td class="px-4 py-3">{{ $transaction->DocumentNumber ?: '-' }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">₱{{ number_format((float) $transaction->AmountSpent, 2) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold tabular-nums {{ $transaction->PointsChanged < 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ $transaction->PointsChanged > 0 ? '+' : '' }}{{ number_format((float) $transaction->PointsChanged, 2) }}</td>
                                    <td class="px-4 py-3">{{ $transaction->TransactionType }}</td>
                                    <td class="py-3 pl-4 text-slate-600">{{ $transaction->Notes ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="py-10 text-center text-slate-500">No transactions match these filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $transactions->links() }}</div>
            </section>
        @endif
    </div>
</div>
