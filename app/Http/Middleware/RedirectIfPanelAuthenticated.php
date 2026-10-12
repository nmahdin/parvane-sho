<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfPanelAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get(config('panel.session_key'), false)) {
            return redirect()->route('panel.dashboard');
        }

        return $next($request);
    }
}
