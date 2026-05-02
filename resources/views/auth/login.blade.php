<x-guest-layout>
    <div style="margin-bottom:1rem;">
        <p style="margin:0;color:#1f4f9f;font-size:.75rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;">Masuk Sistem</p>
        <h2 style="margin:.45rem 0 0;font-family:'Sora',sans-serif;font-size:1.7rem;line-height:1.2;">Selamat datang kembali</h2>
        <p style="margin:.45rem 0 0;color:#5a657a;font-size:.92rem;">Gunakan akun owner, admin, atau kasir untuk melanjutkan.</p>
    </div>

    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" style="display:grid;gap:.9rem;">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;margin-bottom:.38rem;">
                <x-input-label for="password" :value="__('Password')" style="margin-bottom:0;" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" style="font-size:.76rem;font-weight:700;color:#1f4f9f;text-decoration:none;">
                        Lupa password?
                    </a>
                @endif
            </div>
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <label for="remember_me" style="display:inline-flex;align-items:center;gap:.45rem;color:#45556c;font-size:.88rem;">
            <input id="remember_me" type="checkbox" name="remember" style="width:16px;height:16px;accent-color:#175cd3;">
            <span>Ingat saya</span>
        </label>

        <x-primary-button>
            {{ __('Log in') }}
        </x-primary-button>

        <div style="border:1px solid rgba(16,24,39,.12);background:rgba(248,250,252,.9);padding:.7rem .8rem;border-radius:.75rem;color:#4b5567;font-size:.79rem;line-height:1.6;">
            <p style="margin:0 0 .2rem;font-weight:800;color:#1f2937;">Akun Demo</p>
            <p style="margin:0;">owner@bintang.test / password</p>
            <p style="margin:0;">admin@bintang.test / password</p>
            <p style="margin:0;">kasir@bintang.test / password</p>
        </div>
    </form>
</x-guest-layout>
