<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

use function PHPUnit\Framework\isNull;

class CekLevel
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$levels): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Anda harus login untuk mengakses halaman ini.');
        }

        $user = Auth::user();

        // Check if user has the level property
        // dd($user->level);
        if (empty($user->level)) {
            return abort(500, 'Level property not found on user model.');
        }

        // Check if user has required level
        if (in_array($user->level, $levels)) {
            return $next($request);
        }

        // User doesn't have required level
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return redirect()->route('home')
            ->with('error', 'Anda tidak memiliki izin untuk mengakses halaman tersebut.');
    }
}