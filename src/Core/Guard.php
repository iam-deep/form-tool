<?php

namespace Deep\FormTool\Core;

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

    protected static $guardTypes = ['view', 'create', 'edit', 'delete', 'destroy'];

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
        // The construct must remain private
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

    public static function can(string $guardType, string $route = '')
    {
        $guardType = self::validateGuardType($guardType);
        $method = 'has'.\ucfirst($guardType);

        return self::$method($route);
    }

    public function doCheck(Request $request, Closure $next)
    {
        if (! self::$isEnable) {
            return $next($request);
        }

        $this->getLaravelRoute();

        $user = Auth::user();

        $group = DB::table('user_groups')->where('groupId', $user->groupId)->first();
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

            case 'delete':
                if (! $this->hasDelete) {
                    $this->abort();
                }

                break;

            case 'destroy':
                if (! $this->hasDestroy) {
                    $this->abort();
                }

                break;

            default:
        }

        return $next($request);
    }

    public static function abort()
    {
        abort(403, "You don't have enough permission to perform this action!");
    }

    public static function validateGuardType(string $type)
    {
        $type = strtolower(\trim($type));
        if (! in_array($type, self::$guardTypes)) {
            throw new \InvalidArgumentException(\sprintf(
                'Guard type "%s" is not valid. Valid Types are: (%s)',
                $type,
                \implode(', ', self::$guardTypes)
            ));
        }

        return $type;
    }

    public function getLaravelRoute()
    {
        if ($this->route) {
            return $this->route;
        }

        // Let's get the route and action
        $currentRoute = Route::currentRouteName();
        if ($currentRoute) {
            $segments = \explode('.', $currentRoute);
            $count = count($segments);
            if ($count > 1) {
                $this->route = implode('/', array_slice($segments, 0, $count - 1));
                $this->action = end($segments);
            } else {
                $this->route = $segments[0] ?? null;
            }
        } else {
            $currentRoute = request()->path();
            $this->route = \substr($currentRoute, \strlen(\config('form-tool.adminURL')));

            // Let's remove the action if we have any
            $pos = \strrpos($this->route, '/');
            if ($pos !== false) {
                $this->route = \substr($this->route, 0, $pos);
            }

            $currentAction = Route::currentRouteAction();
            $this->action = \substr($currentAction, \strpos($currentAction, '@') + 1);
        }

        return $this->route;
    }
}
