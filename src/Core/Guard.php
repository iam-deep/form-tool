<?php

namespace Biswadeep\FormTool\Core;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

class Guard
{
    protected $route = null;
    protected $action = null;

    protected $permissions = null;

    protected bool $hasView = false;
    protected bool $hasCreate = false;
    protected bool $hasEdit = false;
    protected bool $hasDelete = false;

    public static $instance = null;
    private static bool $isEnable = false;

    /**
     * This function must called first from middleware to initialize the guard class
     * This function must called only once.
     **/
    public static function init(Request $request, Closure $next)
    {
        self::$instance = new Guard();

        return self::$instance->doCheck($request, $next);
    }

    // Private is to prevent direct instantiation of this class
    private function __construct()
    {
        self::$isEnable = config('form-tool.isGuarded', true);
    }

    public static function hasView(string $route = '')
    {
        if (! self::$isEnable) {
            return true;
        }

        if ($route) {
            return isset(self::$instance->permissions->{$route}->view) ? true : false;
        }

        return self::$instance->$hasView;
    }

    public static function hasCreate()
    {
        return ! self::$isEnable || self::$instance->hasCreate;
    }

    public static function hasEdit()
    {
        return ! self::$isEnable || self::$instance->hasEdit;
    }

    public static function hasDelete()
    {
        return ! self::$isEnable || self::$instance->hasDelete;
    }

    public static function hasViewOrAbort()
    {
        if (! self::$isEnable || self::$instance->hasView) {
            return;
        }

        self::abort();
    }

    public static function hasCreateOrAbort()
    {
        if (! self::$isEnable || self::$instance->hasCreate) {
            return;
        }

        self::abort();
    }

    public static function hasEditOrAbort()
    {
        if (! self::$isEnable || self::$instance->hasEdit) {
            return;
        }

        self::abort();
    }

    public static function hasDeleteOrAbort()
    {
        if (! self::$isEnable || self::$instance->hasDelete) {
            return;
        }

        self::abort();
    }

    public function doCheck(Request $request, Closure $next)
    {
        if (! self::$isEnable) {
            return $next($request);
        }

        $this->getLaravelRoute();

        if (Session::has('user')) {
            $sessionUser = Session::get('user');

            if ($sessionUser) {
                $group = DB::table('user_groups')->where('groupId', $sessionUser->groupId)->first();
                if (! isset($group->permission)) {
                    $this->abort();
                }

                $this->permissions = \json_decode($group->permission);

                // Check first if we have view permission
                if (isset($this->permissions->{$this->route}->view)) {
                    $this->hasView = true;
                } else {
                    $this->abort();
                }

                $this->hasCreate = isset($this->permissions->{$this->route}->create) ? true : false;
                $this->hasEdit = isset($this->permissions->{$this->route}->edit) ? true : false;
                $this->hasDelete = isset($this->permissions->{$this->route}->delete) ? true : false;

                // Check permissions as per request action
                switch ($this->action) {
                    case 'create':
                    case 'store':
                    case 'add':
                        if (! $this->hasCreate) {
                            $this->abort();
                        }

                        break;

                    case 'edit':
                    case 'update':
                        if (! $this->hasEdit) {
                            $this->abort();
                        }

                        break;

                    case 'destroy':
                    case 'delete':
                        if (! $this->hasDelete) {
                            $this->abort();
                        }

                        break;
                }

                return $next($request);
            }
        }

        // If we have any issues then just logout the user
        Session::pull('user');

        return redirect(config('form-tool.adminURL').'/login')->with('error', 'Something went wrong! Please loin again.');
    }

    public static function abort()
    {
        abort(403, "You don't have enough permission to perform this action!");
    }

    private function getLaravelRoute()
    {
        // Let's get the route and action
        $currentRoute = Route::currentRouteName();
        if ($currentRoute) {
            $segments = \explode('.', $currentRoute);
            $this->route = $segments[0] ?? null;
            $this->action = $segments[1] ?? null;
        } else {
            $currentRoute = request()->path();
            $this->route = \substr($currentRoute, \strlen(\config('form-tool.adminURL')));

            $currentAction = Route::currentRouteAction();
            $this->action = \substr($currentAction, \strpos($currentAction, '@') + 1);
        }
    }
}
