<section class="space-y-6">
    <header>
        <h2 class="text-xl font-bold text-rose-700">Hapus Akun</h2>
        <p class="mt-2 text-sm text-slate-600">Setelah dihapus, seluruh data akun akan hilang permanen.</p>
    </header>

    <x-danger-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">
        {{ __('Delete Account') }}
    </x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6 bg-white rounded-2xl border border-slate-200">
            @csrf
            @method('delete')

            <h2 class="text-lg font-bold text-slate-900">Yakin ingin menghapus akun?</h2>
            <p class="mt-2 text-sm text-slate-600">Masukkan password untuk konfirmasi penghapusan permanen akun.</p>

            <div class="mt-5">
                <x-input-label for="password" value="{{ __('Password') }}" />
                <x-text-input id="password" name="password" type="password" class="mt-1" placeholder="{{ __('Password') }}" />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <x-secondary-button x-on:click="$dispatch('close')">{{ __('Cancel') }}</x-secondary-button>
                <x-danger-button>{{ __('Delete Account') }}</x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
