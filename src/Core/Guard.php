<?php

namespace Deep\FormTool\Core;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

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
     * This function can be called from middleware to initialize the guard class.
     **/
    public static function init(Request $request, ?Closure $next = null, ?string $permissions = null, ?string $route = null)
    {
        if (! isset(self::$instance)) {
            self::$instance = new Guard();
        }

        if (! self::$isEnable) {
            if ($next) {
                return $next($request);
            }

            return;
        }

        self::$instance->route = $route;
        self::$instance->getLaravelRoute();

        $user = null;
        if (! $permissions) {
            $user = Auth::user();
            $permissions = $user->permission ?? null;
        }

        if (! $permissions) {
            $groupIdCol = config('form-tool.userColumns.groupId', 'groupId');

            $group = DB::table('user_groups')->where('groupId', $user->{$groupIdCol})->first();
            if (! isset($group->permission)) {
                return self::abort($request);
            }
            $permissions = $group->permission;
        }

        self::$instance->permissions = \json_decode($permissions);

        $response = self::$instance->doCheck($request);
        if ($response !== true) {
            return $response;
        }

        if ($next) {
            return $next($request);
        }
    }

    // Private is to prevent direct instantiation of this class
    final private function __construct()
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

        return self::abort(request());
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

        return self::abort(request());
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

        return self::abort(request());
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

        return self::abort(request());
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

        return self::abort(request());
    }

    public static function can(string $guardType, string $route = '')
    {
        $guardType = self::validateGuardType($guardType);
        $method = 'has'.\ucfirst($guardType);

        return self::$method($route);
    }

    public function doCheck(Request $request)
    {
        // Check first if we have view permission
        if (isset($this->permissions->{$this->route}->view)) {
            $this->hasView = true;
        } else {
            return $this->abort($request);
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
                    return $this->abort($request);
                }

                break;

            case 'edit':
            case 'update':
                if (! $this->hasEdit) {
                    return $this->abort($request);
                }

                break;

            case 'delete':
                if (! $this->hasDelete) {
                    return $this->abort($request);
                }

                break;

            case 'destroy':
                if (! $this->hasDestroy && (config('form-tool.isSoftDelete') || ! $this->hasDelete)) {
                    return $this->abort($request);
                }

                break;

            default:
        }

        return true;
    }

    public static function abort($request)
    {
        if ($request->wantsJson()) {
            return ['status' => false, 'message' => "You don't have enough permission to perform this action!"];
        }

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

            if (($pos = strpos($this->route, 'api/')) !== false) {
                $this->route = substr($this->route, $pos + strlen('api/'));
            }

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
