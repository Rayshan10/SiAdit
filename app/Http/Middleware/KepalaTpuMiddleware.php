<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KepalaTpuMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Menjaga fitur yang hanya boleh diakses Admin & Kepala TPU.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        if (!in_array($request->user()->role, ['kepala_tpu', 'admin'])) {
            abort(403, 'Akses ditolak: menu ini hanya untuk Admin dan Kepala TPU.');
        }

        return $next($request);
    }
}
