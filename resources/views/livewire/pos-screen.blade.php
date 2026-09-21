<div
    x-data="{ hardwareStatus: 'Printer not connected.' }"
    @pos:hardware-status.window="hardwareStatus = $event.detail"
    @keydown.escape.window="if ($wire.error || $wire.message) { $wire.set('error', ''); $wire.set('message', ''); } else { document.getElementById('barcode-search')?.focus(); }"
    @keydown.ctrl.d.window.prevent="window.dispatchEvent(new CustomEvent('pos:open-cash-drawer'))"
    class="min-h-screen bg-slate-100 p-6 text-slate-800"
>
    <div class="mx-auto max-w-7xl">
        <header class="mb-6 flex items-center justify-between rounded-2xl bg-slate-900 p-5 text-white shadow-lg">
            <div>
                <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Point of Sale</p>
                <h1 class="mt-2 text-3xl font-bold">POS Screen</h1>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" x-on:click="window.posHardware.connectPrinter()" class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-medium hover:bg-sky-500">
                    Connect Serial
                </button>
                <button type="button" x-on:click="window.posHardware.connectUsb()" class="rounded-xl bg-cyan-600 px-4 py-2 text-sm font-medium hover:bg-cyan-500">
                    Connect USB
                </button>
                <button type="button" x-on:click="window.posHardware.openDrawer()" class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-medium text-slate-950 hover:bg-amber-400">
                    Open Drawer
                </button>
                <button type="button" x-on:click="window.posHardware.lastReceipt ? window.posHardware.printReceipt(window.posHardware.lastReceipt) : (hardwareStatus = 'No receipt available to reprint.')" class="rounded-xl bg-slate-700 px-4 py-2 text-sm font-medium hover:bg-slate-600">
                    Reprint
                </button>
                <a href="{{ route('dashboard') }}" class="rounded-xl bg-slate-700 px-4 py-2 text-sm font-medium hover:bg-slate-600">
                    Dashboard
                </a>
                <button wire:click="clearCart" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-medium hover:bg-rose-400">
                    Clear Cart
                </button>
            </div>
        </header>

        <p class="mb-6 text-right text-xs text-slate-500" x-text="hardwareStatus"></p>

        @if ($message)
            <div
                wire:keydown.escape="$set('message', '')"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4"
                role="presentation"
                wire:click="$set('message', '')"
            >
                <div
                    class="w-full max-w-md rounded-2xl border border-emerald-200 bg-white p-6 shadow-2xl"
                    role="alertdialog"
                    aria-modal="true"
                    aria-labelledby="payment-message-title"
                    wire:click.stop
                >
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600" aria-hidden="true">✓</div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-4">
                                <h2 id="payment-message-title" class="text-lg font-bold text-slate-900">Payment completed</h2>
                                <button
                                    type="button"
                                    wire:click="$set('message', '')"
                                    class="rounded-lg p-1 text-xl leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                                    aria-label="Close information message"
                                >
                                    &times;
                                </button>
                            </div>
                            <p class="mt-2 text-sm text-slate-600">{{ $message }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($error)
            <div
                wire:keydown.escape="$set('error', '')"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4"
                role="presentation"
                wire:click="$set('error', '')"
            >
                <div
                    class="w-full max-w-md rounded-2xl border border-rose-200 bg-white p-6 shadow-2xl"
                    role="alertdialog"
                    aria-modal="true"
                    aria-labelledby="payment-error-title"
                    wire:click.stop
                >
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600" aria-hidden="true">!</div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-4">
                                <h2 id="payment-error-title" class="text-lg font-bold text-slate-900">Payment could not be processed</h2>
                                <button
                                    type="button"
                                    wire:click="$set('error', '')"
                                    class="rounded-lg p-1 text-xl leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                                    aria-label="Close error message"
                                >
                                    &times;
                                </button>
                            </div>
                            <p class="mt-2 text-sm text-slate-600">{{ $error }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[1.4fr_0.8fr]">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="mb-5 flex items-center justify-between gap-4">
                    <h2 class="text-xl font-bold">Products</h2>
                    <input id="barcode-search" wire:model.live.debounce.150ms="search" wire:keydown.enter.prevent="scanBarcode" type="search" placeholder="Search name, code, or scan barcode..." class="w-64 rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-indigo-500" autocomplete="off" />
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($this->filteredItems as $item)
                        <button wire:click="addToCart({{ $item['id'] }})" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-left transition hover:border-indigo-400 hover:bg-indigo-50">
                            <div class="mb-4 flex h-20 items-center justify-center rounded-xl bg-linear-to-br from-indigo-500 to-sky-500 text-3xl font-bold text-white">
                                {{ strtoupper(substr($item['name'], 0, 1)) }}
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-semibold">{{ $item['name'] }}</p>
                                    <p class="text-sm text-slate-500">
                                        {{ $item['is_service'] ? 'Service' : number_format($item['stock'], 0).' in stock' }}
                                        @if ($item['code']) · {{ $item['code'] }} @endif
                                    </p>
                                </div>
                                <span class="text-lg font-bold text-indigo-600">₱{{ number_format($item['price'], 2) }}</span>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

            <aside class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-4 text-xl font-bold">Current Sale</h2>

                <div class="space-y-3">
                    @forelse ($cart as $item)
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 p-3">
                            <div>
                                <p class="font-semibold">{{ $item['name'] }}</p>
                                <p class="text-sm text-slate-500">₱{{ number_format($item['price'], 2) }} each</p>
                                <label class="mt-2 flex items-center gap-2 text-xs text-slate-500">
                                    Discount %
                                    <input type="text" inputmode="decimal" autocomplete="off" value="{{ $item['discount'] }}" wire:change="setItemDiscount({{ $item['id'] }}, $event.target.value)" class="w-20 rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs" />
                                </label>
                            </div>
                            <div class="flex items-center gap-2">
                                <button wire:click="decrement({{ $item['id'] }})" class="h-8 w-8 rounded-lg bg-slate-200 font-bold">-</button>
                                <span class="min-w-6 text-center font-semibold">{{ $item['quantity'] }}</span>
                                <button wire:click="increment({{ $item['id'] }})" class="h-8 w-8 rounded-lg bg-slate-200 font-bold">+</button>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500">
                            No items added yet.
                        </div>
                    @endforelse
                </div>

                <div class="mt-6 space-y-3 border-t border-slate-200 pt-4 text-sm">
                    <div class="flex justify-between"><span>Subtotal</span><span>₱{{ number_format($this->subtotal, 2) }}</span></div>
                    <div class="flex justify-between text-lg font-bold"><span>Total</span><span>₱{{ number_format($this->total, 2) }}</span></div>
                </div>

                <div class="mt-4">
                    <label for="order-discount" class="mb-2 block text-sm font-medium text-slate-600">Discount on total</label>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-slate-500">$</span>
                        <input id="order-discount" type="number" min="0" step="0.01" wire:model.live="orderDiscount" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-indigo-500" />
                    </div>
                </div>

                <div class="mt-6">
                    <label for="customer" class="mb-2 block text-sm font-medium text-slate-600">Customer</label>
                    <select id="customer" wire:model="selectedCustomerId" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                        @foreach ($this->customers as $customer)
                            <option value="{{ $customer['id'] }}">{{ $customer['name'] }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Defaults to Walk-in customer.</p>
                </div>

                <div class="mt-6">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-sm font-medium text-slate-600">Payment method</span>
                        <button type="button" wire:click="enableSplitPayment" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Split payment</button>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($this->paymentTypes as $paymentType)
                            <button type="button" wire:click="selectPaymentType({{ $paymentType['id'] }})" class="rounded-xl border px-3 py-2 text-sm font-semibold {{ !$splitPayment && $selectedPaymentTypeId === $paymentType['id'] ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-slate-300 bg-slate-50 text-slate-700' }}">
                                {{ $paymentType['name'] }}
                            </button>
                        @endforeach
                    </div>

                    @php($selectedPaymentType = collect($this->paymentTypes)->firstWhere('id', $selectedPaymentTypeId))
                    @if (! $splitPayment && strtolower((string) ($selectedPaymentType['name'] ?? '')) === 'cash')
                        <div class="mt-3 rounded-xl border border-emerald-100 bg-emerald-50 p-3">
                            <label for="tendered-amount" class="mb-2 block text-sm font-medium text-emerald-900">Amount tendered</label>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-emerald-700">$</span>
                                <input
                                    id="tendered-amount"
                                    type="text"
                                    inputmode="decimal"
                                    autocomplete="off"
                                    wire:model.live.debounce.150ms="tenderedAmount"
                                    placeholder="0.00"
                                    class="w-full rounded-xl border border-emerald-200 bg-white px-3 py-2 text-sm outline-none focus:border-emerald-500"
                                />
                            </div>
                            <div class="mt-3 flex justify-between text-sm">
                                <span class="text-emerald-800">Change</span>
                                <span class="font-bold text-emerald-900">₱{{ number_format(max(0, $this->tenderedAmountValue - $this->total), 2) }}</span>
                            </div>
                        </div>
                    @endif

                    @if ($splitPayment)
                        <div class="mt-3 space-y-2 rounded-xl border border-indigo-100 bg-indigo-50 p-3">
                            <div class="flex items-center justify-between text-xs text-indigo-700">
                                <span>Split allocations</span>
                                <span>Remaining: ₱{{ number_format(max(0, $this->total - collect($paymentAllocations)->sum('amount')), 2) }}</span>
                            </div>
                            @foreach ($paymentAllocations as $index => $allocation)
                                <div class="flex items-center gap-2">
                                    <select wire:model="paymentAllocations.{{ $index }}.payment_type_id" class="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-2 py-2 text-xs">
                                        @foreach ($this->paymentTypes as $paymentType)
                                            <option value="{{ $paymentType['id'] }}">{{ $paymentType['name'] }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" min="0" step="0.01" wire:model.live="paymentAllocations.{{ $index }}.amount" class="w-24 rounded-lg border border-slate-300 bg-white px-2 py-2 text-xs" />
                                    <button type="button" wire:click="removeSplitPayment({{ $index }})" class="text-rose-600">×</button>
                                </div>
                            @endforeach
                            <div class="flex flex-wrap gap-2 pt-1">
                                @foreach ($this->paymentTypes as $paymentType)
                                    <button type="button" wire:click="addSplitPayment({{ $paymentType['id'] }})" class="rounded-lg border border-indigo-200 bg-white px-2 py-1 text-xs text-indigo-700">+ {{ $paymentType['name'] }}</button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <button wire:click="processPayment" class="mt-6 w-full rounded-xl bg-emerald-500 px-4 py-3 font-semibold text-slate-950 hover:bg-emerald-400">
                    Process Payment
                </button>
            </aside>
        </div>
    </div>
</div>
