<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if (!$user->is_active) {
            return redirect()->route('dashboard')->with('message', 'Akun Anda dinonaktifkan. Hubungi administrator.');
        }

        if ($user->role === $role) {
            return $next($request);
        }

        abort(403, 'Akses ditolak. Halaman ini hanya untuk role: ' . $role);
    }
}
