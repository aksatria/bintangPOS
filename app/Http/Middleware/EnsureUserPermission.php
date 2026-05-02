<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();
        $simulatedId = (int) ($request->session()->get('simulation_user_id', 0));
        $isSimulating = $simulatedId > 0;
        if ($isSimulating) {
            if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
                abort(403, 'Simulation mode hanya untuk baca (read-only).');
            }
            $simulated = User::query()->find($simulatedId);
            if ($simulated) {
                $user = $simulated;
            }
        }

        if (! $user) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses fitur ini.');
        }

        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        abort(403, 'Anda tidak memiliki izin untuk mengakses fitur ini.');
    }
}
