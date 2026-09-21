<div class="min-h-screen bg-slate-900 text-slate-100 flex items-center justify-center px-4">
    <div class="w-full max-w-md rounded-2xl border border-slate-700 bg-slate-800 p-8 shadow-2xl">
        <div class="mb-8 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-500/20 text-2xl text-emerald-400">P</div>
            <h1 class="mt-4 text-3xl font-bold">POSZen</h1>
            <p class="mt-2 text-sm text-slate-400">Sign in to your dashboard</p>
        </div>

        <form wire:submit.prevent="login" class="space-y-5">
            @if ($error)
                <div class="rounded-lg border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-sm text-rose-300">
                    {{ $error }}
                </div>
            @endif

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-300">Password</label>
                <input wire:model="password" type="password" class="w-full rounded-xl border border-slate-600 bg-slate-900 px-4 py-3 text-slate-100 outline-none ring-0 transition focus:border-emerald-400" placeholder="••••••••" />
                @error('password') <span class="mt-1 block text-xs text-rose-400">{{ $message }}</span> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-300">
                <input wire:model="remember" type="checkbox" class="rounded border-slate-600 bg-slate-900 text-emerald-500 focus:ring-emerald-500" />
                Remember me
            </label>

            <button type="submit" class="w-full rounded-xl bg-emerald-500 px-4 py-3 font-semibold text-slate-950 transition hover:bg-emerald-400">
                Sign In
            </button>
        </form>
    </div>
</div>
