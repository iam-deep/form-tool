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
    protected bool $hasDestroy = false;

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

        return self::$instance->hasView;
    }

    public static function hasCreate(string $route = '')
    {
        if (! self::$isEnable) {
            return true;
        }

        if ($route) {
            $permission = self::$instance->permissions;

            return isset($permission->{$route}->view, $permission->{$route}->create) ? true : false;
        }

        return self::$instance->hasView && self::$instance->hasCreate;
    }

    public static function hasEdit(string $route = '')
    {
        if (! self::$isEnable) {
            return true;
        }

        if ($route) {
            $permission = self::$instance->permissions;

            return isset($permission->{$route}->view, $permission->{$route}->edit) ? true : false;
        }

        return self::$instance->hasView && self::$instance->hasEdit;
    }

    public static function hasDelete(string $route = '')
    {
        if (! self::$isEnable) {
            return true;
        }

        if ($route) {
            $permission = self::$instance->permissions;

            return isset($permission->{$route}->view, $permission->{$route}->delete) ? true : false;
        }

        return self::$instance->hasView && self::$instance->hasDelete;
    }

    public static function hasDestroy(string $route = '')
    {
        if (! self::$isEnable) {
            return true;
        }

        if ($route) {
            $permission = self::$instance->permissions;

            return isset($permission->{$route}->view, $permission->{$route}->destroy) ? true : false;
        }

        return self::$instance->hasView && self::$instance->hasDestroy;
    }

    public static function hasViewOrAbort(string $route = '')
    {
        if (! self::$isEnable) {
            return;
        }

        if ($route) {
            $permission = self::$instance->permissions;
            if (isset($permission->{$route}->view)) {
                return;
            }
        }

        if (self::$instance->hasView) {
            return;
        }

        self::abort();
    }

    public static function hasCreateOrAbort(string $route = '')
    {
        if (! self::$isEnable) {
            return;
        }

        if ($route) {
            $permission = self::$instance->permissions;
            if (isset($permission->{$route}->view, $permission->{$route}->create)) {
                return;
            }
        }

        if (self::$instance->hasCreate) {
            return;
        }

        self::abort();
    }

    public static function hasEditOrAbort(string $route = '')
    {
        if (! self::$isEnable) {
            return;
        }

        if ($route) {
            $permission = self::$instance->permissions;
            if (isset($permission->{$route}->view, $permission->{$route}->edit)) {
                return;
            }
        }

        if (self::$instance->hasEdit) {
            return;
        }

        self::abort();
    }

    public static function hasDeleteOrAbort(string $route = '')
    {
        if (! self::$isEnable) {
            return;
        }

        if ($route) {
            $permission = self::$instance->permissions;
            if (isset($permission->{$route}->view, $permission->{$route}->delete)) {
                return;
            }
        }

        if (self::$instance->hasDelete) {
            return;
        }

        self::abort();
    }

    public static function hasDestroyOrAbort(string $route = '')
    {
        if (! self::$isEnable) {
            return;
        }

        if ($route) {
            $permission = self::$instance->permissions;
            if (isset($permission->{$route}->view, $permission->{$route}->destroy)) {
                return;
            }
        }

        if (self::$instance->hasDestroy) {
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
                $this->hasDestroy = isset($this->permissions->{$this->route}->destroy) ? true : false;

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

        return redirect(config('form-tool.adminURL').'/login')->with('error', 'Something went wrong! Please login again.');
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
