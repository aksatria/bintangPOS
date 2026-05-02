<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Akun</p>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Pengaturan Profil</h2>
        </div>
    </x-slot>

    <div class="page-shell space-y-6">
        <div class="panel-card p-6">
            <div class="max-w-2xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="panel-card p-6">
            <div class="max-w-2xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="panel-card p-6 border-rose-200 bg-rose-50/40">
            <div class="max-w-2xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
