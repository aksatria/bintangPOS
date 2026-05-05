<x-guest-layout>
    <div style="margin-bottom:1rem;">
        <p style="margin:0;color:#1f4f9f;font-size:.75rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;">Verifikasi Login</p>
        <h2 style="margin:.45rem 0 0;font-family:'Sora',sans-serif;font-size:1.7rem;line-height:1.2;">Masukkan kode keamanan</h2>
        <p style="margin:.45rem 0 0;color:#5a657a;font-size:.92rem;">
            Kami mengirim kode 6 digit ke email {{ $email !== '' ? $email : 'akun Anda' }}.
        </p>
    </div>

    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('two-factor.challenge.store') }}" style="display:grid;gap:.9rem;">
        @csrf
        <div>
            <x-input-label for="code" :value="__('Kode Verifikasi')" />
            <x-text-input id="code" type="text" name="code" :value="old('code')" required autofocus inputmode="numeric" maxlength="6" />
            <x-input-error :messages="$errors->get('code')" />
        </div>

        <x-primary-button>
            Verifikasi
        </x-primary-button>
    </form>

    <form method="POST" action="{{ route('two-factor.challenge.resend') }}" style="margin-top:.8rem;">
        @csrf
        <button type="submit" style="background:none;border:none;padding:0;color:#1f4f9f;font-weight:700;font-size:.84rem;cursor:pointer;">
            Kirim ulang kode
        </button>
    </form>
</x-guest-layout>

