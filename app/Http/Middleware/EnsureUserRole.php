<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $simulatedId = (int) ($request->session()->get('simulation_user_id', 0));
        if ($simulatedId > 0) {
            if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
                abort(403, 'Simulation mode hanya untuk baca (read-only).');
            }
            $simulated = User::query()->find($simulatedId);
            if ($simulated) {
                $user = $simulated;
            }
        }

        if (! $user || ! $user->hasAnyRole($roles)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
