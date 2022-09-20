<?php

namespace Biswadeep\FormTool\Http\Middleware;

use Biswadeep\FormTool\Core\Guard;
use Closure;
use Illuminate\Http\Request;

class GuardRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        return Guard::init($request, $next);
    }
}
