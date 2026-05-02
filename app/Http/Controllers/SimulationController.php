<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class SimulationController extends Controller
{
    public function start(Request $request)
    {
        abort_unless($request->user()?->hasAnyRole(['owner']), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $target = User::query()->find((int) $validated['user_id']);
        if (! $target) {
            return back()->with('error', 'User simulasi tidak ditemukan.');
        }

        $request->session()->put('simulation_user_id', (int) $target->id);
        $request->session()->put('simulation_user_name', (string) $target->name);
        $request->session()->put('simulation_role', (string) ($target->role?->value ?? $target->role ?? '-'));

        return back()->with('status', 'Simulation mode aktif: '.$target->name);
    }

    public function stop(Request $request)
    {
        $request->session()->forget(['simulation_user_id', 'simulation_user_name', 'simulation_role']);
        return back()->with('status', 'Simulation mode dimatikan.');
    }
}

