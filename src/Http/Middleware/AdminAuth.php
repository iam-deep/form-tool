<?php

namespace Biswadeep\FormTool\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Biswadeep\FormTool\Core\Auth;

class AdminAuth
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
        $msg = 'Please login to continue!';

        if (Session::has('user')) {
            $sessionUser = Session::get('user');

            if ($sessionUser) {
                $user = DB::table('users')->where('email', $sessionUser->email)->where('status', 1)->first();
                if ($user && isset($sessionUser->adminLoginToken) && Hash::check($user->password.$user->email.$_SERVER['HTTP_USER_AGENT'], $sessionUser->adminLoginToken)) {
                    Auth::setUser($user);
                    return $next($request);
                }
            }

            $msg = 'Your session has been expired!';
        }

        Session::pull('user');

        return redirect()->route('login')->with('error', $msg);
    }
}
