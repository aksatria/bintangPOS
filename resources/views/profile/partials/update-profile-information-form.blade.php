<section>
    <header>
        <h2 class="text-xl font-bold text-slate-900">Informasi Profil</h2>
        <p class="mt-2 text-sm text-slate-500">Perbarui nama dan email akun Anda.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                    <p>
                        Email Anda belum terverifikasi.
                        <button form="send-verification" class="font-semibold underline">Kirim ulang link verifikasi</button>
                    </p>
                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-emerald-700 font-medium">Link verifikasi baru sudah dikirim.</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>
            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-slate-500">Tersimpan.</p>
            @endif
        </div>
    </form>
</section>
