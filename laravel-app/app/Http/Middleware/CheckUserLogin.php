<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckUserLogin
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || auth()->user()->role !== 'user') {
            return redirect()->route('login')->with('error', 'Anda harus login terlebih dahulu!');
        }

        return $next($request);
    }
}
