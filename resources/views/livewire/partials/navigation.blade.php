@php
    $navigationItems = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'fa-gauge-high'],
        ['label' => 'Customer', 'route' => 'customers', 'icon' => 'fa-users'],
        ['label' => 'Products', 'route' => 'products', 'icon' => 'fa-box'],
        ['label' => 'Contacts', 'route' => 'contacts', 'icon' => 'fa-address-book'],
    ];
@endphp

<nav aria-label="Main navigation" class="mb-8 flex flex-wrap items-center gap-2 rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-200">
    @foreach ($navigationItems as $item)
        <a href="{{ route($item['route']) }}" @if (request()->routeIs($item['route'])) aria-current="page" @endif @class([
            'inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold',
            'bg-slate-900 text-white hover:bg-slate-700' => request()->routeIs($item['route']),
            'text-slate-700 hover:bg-slate-100' => ! request()->routeIs($item['route']),
        ])>
            <i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>{{ $item['label'] }}
        </a>
    @endforeach

    <details class="group relative">
        <summary @class([
            'inline-flex cursor-pointer list-none items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold',
            'bg-slate-900 text-white hover:bg-slate-700' => request()->routeIs('reports*'),
            'text-slate-700 hover:bg-slate-100' => ! request()->routeIs('reports*'),
        ])>
            <i class="fa-solid fa-chart-column" aria-hidden="true"></i>Report
            <i class="fa-solid fa-chevron-down text-xs" aria-hidden="true"></i>
        </summary>
        <div class="absolute left-0 top-full z-30 mt-1 min-w-44 rounded-lg bg-white p-1 shadow-lg ring-1 ring-slate-200">
            <a href="{{ route('reports') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"><i class="fa-solid fa-table-columns" aria-hidden="true"></i>Overview</a>
            <a href="{{ route('reports.sales-trend') }}" @if (request()->routeIs('reports.sales-trend')) aria-current="page" @endif class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"><i class="fa-solid fa-chart-line" aria-hidden="true"></i>Sales Trend</a>
            <a href="{{ route('reports.state-of-account') }}" @if (request()->routeIs('reports.state-of-account')) aria-current="page" @endif class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i>SOA</a>
            <a href="{{ route('reports.product-sales') }}" @if (request()->routeIs('reports.product-sales')) aria-current="page" @endif class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i>Product Sales</a>
        </div>
    </details>

    <div class="ml-auto flex items-center gap-4">
        <span class="text-sm text-slate-500">Welcome, {{ trim((auth()->user()?->FirstName ?? '').' '.(auth()->user()?->LastName ?? '')) ?: 'Admin' }}</span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Logout</button>
        </form>
    </div>
</nav>
