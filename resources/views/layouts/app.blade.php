<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'BINTANG') }}</title>

    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('dist/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom-midone.css') }}?v={{ filemtime(public_path('css/custom-midone.css')) }}">
    @vite(['resources/js/app.js'])

</head>
<body class="app">
    @php
        $storeBrand = \App\Models\StoreSetting::query()->first();
        $storeLogo = (!empty($storeBrand?->logo) && \Illuminate\Support\Facades\Storage::disk('public')->exists((string) $storeBrand->logo))
            ? asset('storage/' . $storeBrand->logo)
            : asset('dist/images/logo.svg');
        $storeName = $storeBrand?->name ?: 'BINTANG';
    @endphp
    <div class="mobile-menu md:hidden" x-data="{ open: false }">
        <div class="mobile-menu-bar">
            <a href="{{ route('dashboard') }}" class="flex mr-auto items-center">
                <img
                    alt="{{ $storeName }}"
                    src="{{ $storeLogo }}"
                    style="width:28px;height:28px;max-width:28px;max-height:28px;object-fit:contain;border-radius:6px;background:rgba(255,255,255,.1);padding:2px;display:block;"
                >
            </a>
            <a href="javascript:;" @click="open = !open">
                <i data-feather="bar-chart-2" class="w-8 h-8 text-white transform -rotate-90"></i>
            </a>
        </div>
        <div x-show="open" class="bg-theme-1 border-t border-theme-24 py-3 px-3">
            @include('layouts.navigation')
        </div>
    </div>

    <div class="flex">
        <nav class="side-nav">
            <a href="{{ route('dashboard') }}" class="intro-x flex items-center pl-5 pt-4">
                <img
                    alt="{{ $storeName }}"
                    src="{{ $storeLogo }}"
                    style="width:28px;height:28px;max-width:28px;max-height:28px;object-fit:contain;border-radius:6px;background:rgba(255,255,255,.1);padding:2px;display:block;flex:0 0 28px;"
                >
                <span
                    class="hidden xl:block text-white text-lg ml-3 font-medium"
                    style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                    title="{{ $storeName }}"
                >{{ $storeName }}</span>
            </a>
            <div class="side-nav__devider my-6"></div>
            @include('layouts.navigation')
        </nav>

        <div class="content">
            <div class="top-bar">
                <div class="-intro-x breadcrumb mr-auto hidden sm:flex">
                    <a href="{{ route('dashboard') }}">Application</a>
                    <i data-feather="chevron-right" class="breadcrumb__icon"></i>
                    @php
                        $breadcrumbLabel = 'Halaman';
                        if (request()->routeIs('dashboard')) {
                            $breadcrumbLabel = 'Dashboard';
                        } elseif (request()->routeIs('pos.*') || request()->routeIs('sales.*')) {
                            $breadcrumbLabel = 'Point of Sale';
                        } elseif (request()->routeIs('reports.*')) {
                            $breadcrumbLabel = 'Laporan';
                        } elseif (request()->routeIs('customers.*')) {
                            $breadcrumbLabel = 'Pelanggan';
                        } elseif (request()->routeIs('audit-logs.*')) {
                            $breadcrumbLabel = 'Audit Log Kasir';
                        } elseif (request()->routeIs('stock-opnames.*')) {
                            $breadcrumbLabel = 'Stock Opname';
                        } elseif (request()->is('admin/categories*') || request()->is('admin/products*') || request()->is('admin/expenses*') || request()->is('admin/users*')) {
                            $breadcrumbLabel = 'Master Data';
                        } elseif (request()->is('admin/store-settings*') || request()->is('admin/notification-settings*')) {
                            $breadcrumbLabel = 'Kontrol & Sistem';
                        } elseif (request()->routeIs('profile.*')) {
                            $breadcrumbLabel = 'Profil';
                        }
                    @endphp
                    <a href="#" class="breadcrumb--active">
                        {{ $breadcrumbLabel }}
                    </a>
                </div>

                <div class="intro-x relative mr-3 sm:mr-6">
                    <div class="search hidden sm:block">
                        <input type="text" class="search__input input placeholder-theme-13" placeholder="Search...">
                        <i data-feather="search" class="search__icon"></i>
                    </div>
                </div>

                <div class="intro-x dropdown w-8 h-8 relative">
                    <a href="{{ route('profile.edit') }}" class="w-8 h-8 rounded-full overflow-hidden shadow-lg image-fit zoom-in bg-theme-1 text-white flex items-center justify-center font-bold">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </a>
                </div>
            </div>

            @php
                $simId = (int) session('simulation_user_id', 0);
                $simName = (string) session('simulation_user_name', '');
                $simRole = (string) session('simulation_role', '');
                $canSimulate = auth()->user()?->hasAnyRole(['owner']) ?? false;
                $simUsers = $canSimulate ? \App\Models\User::query()->orderBy('name')->limit(20)->get(['id','name','role']) : collect();
            @endphp
            @if($canSimulate)
                <div class="mb-3 rounded-xl border {{ $simId > 0 ? 'border-amber-300 bg-amber-50' : 'border-slate-200 bg-slate-50' }} px-3 py-2 flex flex-wrap items-center gap-2">
                    @if($simId > 0)
                        <span class="text-xs font-semibold text-amber-800">Simulation aktif: {{ $simName }} ({{ strtoupper($simRole) }}) - read-only</span>
                        <form method="POST" action="{{ route('admin.simulation.stop') }}">
                            @csrf
                            <button class="text-xs px-2 py-1 rounded border border-amber-300 bg-white text-amber-800 font-semibold">Matikan</button>
                        </form>
                    @else
                        <span class="text-xs font-semibold text-slate-700">Permission Simulation (read-only)</span>
                        <form method="POST" action="{{ route('admin.simulation.start') }}" class="flex items-center gap-2">
                            @csrf
                            <select name="user_id" class="text-xs border border-slate-300 rounded px-2 py-1.5">
                                @foreach($simUsers as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ strtoupper((string) ($u->role?->value ?? $u->role ?? '-')) }})</option>
                                @endforeach
                            </select>
                            <button class="text-xs px-2 py-1 rounded border border-slate-300 bg-white text-slate-700 font-semibold">Simulasikan</button>
                        </form>
                    @endif
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-primary show mb-2" role="alert">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger show mb-2" role="alert">{{ session('error') }}</div>
            @endif

            @if (session('stock_warning'))
                <div class="alert alert-warning show mb-2" role="alert">Stok menipis: {{ implode(', ', session('stock_warning')) }}</div>
            @endif

            @isset($header)
                <div class="mt-6">
                    {{ $header }}
                </div>
            @endisset

            <div class="mt-5">
                {{ $slot }}
            </div>
        </div>
    </div>

    <script src="{{ asset('dist/js/app.js') }}"></script>
    <script>
        (function () {
            function renderIcons() {
                if (window.feather && typeof window.feather.replace === 'function') {
                    window.feather.replace();
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', renderIcons);
            } else {
                renderIcons();
            }

            window.addEventListener('load', renderIcons);
            document.addEventListener('livewire:navigated', renderIcons);
        })();
    </script>
</body>
</html>
