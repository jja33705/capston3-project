<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (!$request->expectsJson()) {
            return route('login');
        }
    }

    public function handle($request, Closure $next, ...$guards)
    {
        //웹은 이걸로 쿠키 확인
        if ($login_token = $request->cookie('login_token')) {
            $request->headers->set('Authorization', 'Bearer ' . $login_token);
        }

        $this->authenticate($request, $guards);

        return $next($request);
    }
}
