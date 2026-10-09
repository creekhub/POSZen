<div class="flex min-h-screen items-center justify-center bg-[#f7f8f3] px-4 py-8 text-[#173f2d]">
    <div class="w-full max-w-md rounded-2xl border border-[#e3e9df] bg-white p-8 shadow-xl shadow-[#173f2d]/10">
        <div class="mb-8 text-center">
            <img src="{{ asset('images/zenthea-store-logo.svg') }}" alt="Zenthea Store" class="mx-auto mb-5 w-full max-w-56 rounded-xl">
            <h1 class="text-2xl font-bold">Sign in</h1>
            <p class="mt-2 text-sm text-slate-500">Access your store dashboard</p>
        </div>

        <form wire:submit.prevent="login" class="space-y-5">
            @if ($error)
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                    {{ $error }}
                </div>
            @endif

            <div>
                <label for="password" class="mb-2 block text-sm font-medium text-slate-700">Password</label>
                <input id="password" wire:model="password" type="password" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15" placeholder="••••••••" />
                @error('password') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input wire:model="remember" type="checkbox" class="rounded border-slate-300 bg-white text-emerald-700 focus:ring-emerald-600" />
                Remember me
            </label>

            <button type="submit" class="w-full rounded-xl bg-[#173f2d] px-4 py-3 font-semibold text-white transition hover:bg-[#24583d] focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                Sign In
            </button>
        </form>
    </div>
</div>
