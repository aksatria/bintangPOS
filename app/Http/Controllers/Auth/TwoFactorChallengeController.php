<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\LoginTwoFactorCodeNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    private const SESSION_KEY = 'auth.mfa.pending';

    public function create(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get(self::SESSION_KEY);
        if (! is_array($pending) || ! isset($pending['token'], $pending['user_id'])) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge', [
            'email' => (string) ($pending['email'] ?? ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $pending = $request->session()->get(self::SESSION_KEY);
        if (! is_array($pending) || ! isset($pending['token'], $pending['user_id'])) {
            return redirect()->route('login')->withErrors(['code' => 'Sesi verifikasi tidak ditemukan.']);
        }

        $cacheKey = $this->cacheKey((string) $pending['token']);
        $state = Cache::get($cacheKey);
        if (! is_array($state) || ! isset($state['hash'], $state['user_id'], $state['attempts'])) {
            $request->session()->forget(self::SESSION_KEY);
            return redirect()->route('login')->withErrors(['code' => 'Kode verifikasi sudah kedaluwarsa.']);
        }

        $attempts = (int) $state['attempts'];
        if ($attempts >= 5) {
            Cache::forget($cacheKey);
            $request->session()->forget(self::SESSION_KEY);
            return redirect()->route('login')->withErrors(['code' => 'Percobaan verifikasi melebihi batas.']);
        }

        if (! hash_equals((string) $state['hash'], hash('sha256', (string) $validated['code']))) {
            $expiresAt = isset($state['expires_at']) ? Carbon::parse((string) $state['expires_at']) : now()->addMinutes(10);
            $ttlSeconds = max(60, now()->diffInSeconds($expiresAt, false));
            $state['attempts'] = $attempts + 1;
            Cache::put($cacheKey, $state, now()->addSeconds($ttlSeconds));

            return back()->withErrors(['code' => 'Kode verifikasi tidak valid.'])->withInput();
        }

        $remember = (bool) ($pending['remember'] ?? false);
        Auth::loginUsingId((int) $pending['user_id'], $remember);
        $request->session()->regenerate();
        $request->session()->put('auth.mfa.passed_at', now()->toIso8601String());
        $request->session()->forget(self::SESSION_KEY);
        Cache::forget($cacheKey);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function resend(Request $request): RedirectResponse
    {
        $pending = $request->session()->get(self::SESSION_KEY);
        if (! is_array($pending) || ! isset($pending['token'], $pending['user_id'])) {
            return redirect()->route('login');
        }

        $user = User::query()->find((int) $pending['user_id']);
        if (! $user) {
            $request->session()->forget(self::SESSION_KEY);
            return redirect()->route('login');
        }

        $this->issueCode($user, (string) $pending['token']);

        return back()->with('status', 'Kode verifikasi baru sudah dikirim.');
    }

    public static function issueCode(User $user, string $token): void
    {
        $ttlMinutes = max(3, (int) env('MFA_CODE_TTL_MINUTES', 10));
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put(self::cacheKey($token), [
            'user_id' => (int) $user->id,
            'hash' => hash('sha256', $code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes($ttlMinutes)->toIso8601String(),
        ], now()->addMinutes($ttlMinutes));

        $user->notify(new LoginTwoFactorCodeNotification($code, $ttlMinutes));
    }

    private static function cacheKey(string $token): string
    {
        return 'auth:mfa:login:'.Str::lower($token);
    }
}
