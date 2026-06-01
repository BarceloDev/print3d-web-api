<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->plan_active) {
            return response()->json([
                'message' => 'Assinatura inativa. Renove seu plano para continuar.',
            ], 403);
        }

        return $next($request);
    }
}
