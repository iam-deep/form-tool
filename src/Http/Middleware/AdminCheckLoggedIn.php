<?php

namespace Biswadeep\FormTool\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class AdminCheckLoggedIn
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
        if (Session::has('user')) {
            $sessionUser = Session::get('user');

            if ($sessionUser) {
                $query = DB::table('users');

                $metaColumns = \config('form-tool.table_meta_columns');
                if (isset($metaColumns['deletedAt']) && \trim($metaColumns['deletedAt'])) {
                    $query->whereNull($metaColumns['deletedAt']);
                }
                
                $user = $query->where('email', $sessionUser->email)->where('status', 1)->first();
                if ($user && isset($sessionUser->adminLoginToken) && Hash::check($user->password.$user->email.$_SERVER['HTTP_USER_AGENT'], $sessionUser->adminLoginToken)) {
                    $loginRedirect = config('form-tool.loginRedirect');
                    if (! $loginRedirect) {
                        $loginRedirect = '/dashboard';
                    }

                    $loginRedirect = config('form-tool.adminURL').$loginRedirect;

                    return redirect($loginRedirect);
                }
            }
        }

        return $next($request);
    }
}
