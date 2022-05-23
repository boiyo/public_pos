<?php

namespace App\Http\Middleware;

use Closure;

class Login
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
		if(!session('userid')){
			return response()->view('login');
			//return redirect()->response()->route('login');
			//return redirect('/');
		}
        return $next($request);
    }
}
