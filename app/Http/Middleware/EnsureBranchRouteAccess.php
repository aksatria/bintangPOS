<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBranchRouteAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->hasAnyRole(['owner'])) {
            return $next($request);
        }

        $actorBranchId = $user->branch_id ? (int) $user->branch_id : null;
        if (! $actorBranchId) {
            return $next($request);
        }

        foreach ((array) $request->route()?->parameters() as $parameter) {
            if (! $parameter instanceof Model) {
                continue;
            }

            $attributes = $parameter->getAttributes();
            if (! array_key_exists('branch_id', $attributes)) {
                continue;
            }

            $targetBranchId = $parameter->getAttribute('branch_id');
            if ($targetBranchId === null) {
                continue;
            }

            if ((int) $targetBranchId !== $actorBranchId) {
                abort(404);
            }
        }

        return $next($request);
    }
}

