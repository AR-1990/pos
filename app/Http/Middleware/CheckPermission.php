<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $module, $action): Response
    {
        if (!Auth::check()) {
            return redirect('login');
        }

        $user = Auth::user();

        // Check if user has permission
        if (!$user->hasPermission($module, $action)) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Unauthorized action.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
