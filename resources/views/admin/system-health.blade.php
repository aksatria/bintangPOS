<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="page-kicker">Kontrol & Sistem</p>
                <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">System Health</h2>
            </div>
            <form method="POST" action="{{ route('admin.system-health.run-now') }}">
                @csrf
                <button type="submit" class="btn-primary px-4 py-2 text-sm">Run Health Check Now</button>
            </form>
        </div>
    </x-slot>

    <div class="page-shell space-y-5">
        @if(session('status'))
            <div class="panel-card p-3 border border-emerald-200 bg-emerald-50 text-emerald-700 text-sm">{{ session('status') }}</div>
        @endif
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <div class="panel-card p-4 border border-rose-200 bg-rose-50">
                <p class="text-xs uppercase tracking-wide text-rose-700 font-semibold">Failed Jobs</p>
                <p class="mt-1 text-2xl font-extrabold text-rose-800">{{ number_format((int) ($metrics['failed_jobs'] ?? 0), 0, ',', '.') }}</p>
            </div>
            <div class="panel-card p-4 border border-amber-200 bg-amber-50">
                <p class="text-xs uppercase tracking-wide text-amber-700 font-semibold">Queue Backlog</p>
                <p class="mt-1 text-2xl font-extrabold text-amber-800">{{ number_format((int) ($metrics['queue_backlog'] ?? 0), 0, ',', '.') }}</p>
            </div>
            <div class="panel-card p-4 border border-sky-200 bg-sky-50">
                <p class="text-xs uppercase tracking-wide text-sky-700 font-semibold">Pending Approval</p>
                <p class="mt-1 text-2xl font-extrabold text-sky-800">{{ number_format((int) ($metrics['pending_approvals'] ?? 0), 0, ',', '.') }}</p>
            </div>
            <div class="panel-card p-4 border border-fuchsia-200 bg-fuchsia-50">
                <p class="text-xs uppercase tracking-wide text-fuchsia-700 font-semibold">Overdue Approval</p>
                <p class="mt-1 text-2xl font-extrabold text-fuchsia-800">{{ number_format((int) ($metrics['overdue_approvals'] ?? 0), 0, ',', '.') }}</p>
            </div>
            <div class="panel-card p-4 border border-slate-200 bg-slate-50">
                <p class="text-xs uppercase tracking-wide text-slate-700 font-semibold">Critical Errors 1h</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-800">{{ number_format((int) ($metrics['critical_errors_1h'] ?? 0), 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="panel-card p-4 border border-slate-200">
            <p class="text-sm font-semibold text-slate-800">Snapshot Terakhir</p>
            @if($lastHealth)
                <p class="mt-1 text-xs text-slate-600">
                    {{ optional($lastHealth->created_at)->format('d/m/Y H:i:s') }} -
                    {{ json_encode($lastHealth->context, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}
                </p>
            @else
                <p class="mt-1 text-xs text-slate-500">Belum ada snapshot health.</p>
            @endif
        </div>
    </div>
</x-app-layout>
