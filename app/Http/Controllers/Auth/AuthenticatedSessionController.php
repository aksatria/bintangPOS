<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();
        if ($user instanceof User && $this->requiresMfa($user)) {
            $token = (string) Str::uuid();
            $request->session()->put('auth.mfa.pending', [
                'token' => $token,
                'user_id' => (int) $user->id,
                'email' => (string) $user->email,
                'remember' => $request->boolean('remember'),
            ]);

            TwoFactorChallengeController::issueCode($user, $token);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->session()->put('auth.mfa.pending', [
                'token' => $token,
                'user_id' => (int) $user->id,
                'email' => (string) $user->email,
                'remember' => $request->boolean('remember'),
            ]);

            return redirect()->route('two-factor.challenge');
        }

        $request->session()->regenerate();
        $request->session()->put('auth.mfa.passed_at', now()->toIso8601String());

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function requiresMfa(User $user): bool
    {
        if (! filter_var(env('MFA_ENABLED', true), FILTER_VALIDATE_BOOL)) {
            return false;
        }

        $roles = collect(explode(',', (string) env('MFA_REQUIRED_ROLES', 'owner,admin')))
            ->map(fn ($role) => trim(Str::lower($role)))
            ->filter()
            ->values()
            ->all();

        $role = Str::lower((string) ($user->role?->value ?? ''));

        return $role !== '' && in_array($role, $roles, true);
    }
}
