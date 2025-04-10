<?php

namespace Deep\FormTool\Support;

use Closure;
use Deep\FormTool\Core\Guard;

class Menu
{
    protected static $menuBag = null;
    public $list = [];
    public $activeLink = '';

    protected string $baseURL = '';
    protected bool $isMenuMade = false;

    protected bool $isParent = false;
    protected string $parentLabel = '';
    protected $parentIcon = '';
    protected bool $isChildActive = false;

    private function __construct()
    {
        // The construct must remain private
        $this->baseURL = createUrl('/');
    }

    /**
     * Create new menu.
     *
     * @param  string  $menuName  optional name of the menu in menu bag, required when creating multiple menus
     * @return Menu
     **/
    public static function create(string $menuName = 'default')
    {
        if (! self::$menuBag) {
            self::$menuBag = new \stdClass();
        }

        $menuName = \trim($menuName);

        $menu = new Menu();
        self::$menuBag->{$menuName} = $menu;

        return $menu;
    }

    /**
     * Add single sidebar menu link.
     *
     * @param  string  $route  Provide link/route of the anchor tag
     * @param  string  $label  Provide label/text of the anchor tag
     * @param  string  $icon  Provide icon class
     * @param  string  $guardUrl  Provide guarded route/key if different from route
     * @param  $guardAction  optionally you can specify action to guard (Values: null, view, create, edit, delete,
     *                      destroy)
     * @return null
     **/
    public function add(
        string $route,
        string $label,
        ?string $icon = '',
        ?string $guardUrl = null,
        $guardAction = 'view',
        $activeType = 'default'
    ) {
        if (! $guardAction || Guard::{'has'.$guardAction}($guardUrl ?: $route)) {
            $this->list[] = (object) [
                'href' => $this->baseURL.'/'.$route,
                'route' => $route,
                'label' => $label,
                'icon' => $icon,
                'active' => false,
                'isParent' => false,
                'activeType' => $activeType,
            ];
        }
    }

    /**
     * Add nested sidebar menu links.
     *
     * @param  string  $parentLabel  Parent menu label
     * @param  string  $parentIcon  Parent menu icon
     * @param  Closure  $childLinks  Child links Closure
     * @return null
     **/
    public function addNested(string $parentLabel, string $parentIcon, Closure $childLinks)
    {
        $menu = new Menu();
        $menu->isParent = true;
        $menu->parentLabel = $parentLabel;
        $menu->parentIcon = $parentIcon;

        $childLinks($menu);

        $this->list[] = $menu;
    }

    private function getActiveLink()
    {
        if ($this->activeLink) {
            return $this->activeLink;
        }

        // TODO: We need to be sure the controller is not getting instantiated twice
        $controller = \Illuminate\Support\Facades\Route::current()?->getController();

        // Let's check if we have a route property in the controller
        if ($controller && isset($controller->route)) {
            $this->activeLink = $controller->route;
        } else {
            $name = \Illuminate\Support\Facades\Route::current()?->getName();
            $this->activeLink = \explode('.', $name)[0] ?? null;

            // This only works without prefix, I don't think this is need as we are using the route and name
            if (! $this->activeLink) {
                $url = str_replace(url(\config('form-tool.adminURL')).'/', '', url()->current());
                $this->activeLink = $url;

                // Let's check if we have opened edit/create/show/index
                // We don't have anything that can select for show() with token
                if (false !== \strpos($url, '/edit')) {
                    $this->activeLink = \substr($url, 0, \strrpos($url, '/', -6));
                } elseif (\preg_match('/(.*)\/create/', $url, $matches) !== false && $matches) {
                    $this->activeLink = $matches[1] ?? null;
                } elseif (\preg_match('/(.*)\/\d.*/', $url, $matches) !== false && $matches) {
                    $this->activeLink = $matches[1] ?? null;
                } elseif (\preg_match('/(.*)/', $url, $matches) !== false && $matches) {
                    $this->activeLink = $matches[1] ?? null;
                }
            }
        }

        return $this->activeLink;
    }

    public function make($active = null)
    {
        if ($this->isMenuMade) {
            return $this->list;
        }

        $this->isMenuMade = true;

        $this->activeLink = $active ?: $this->getActiveLink();

        foreach ($this->list as $key => &$menu) {
            if ($menu instanceof Menu) {
                $newMenu = new \stdClass();
                $newMenu->isParent = true;
                $newMenu->label = $menu->parentLabel;
                $newMenu->icon = $menu->parentIcon;

                // Make the menu and active the parent if any child is active
                $newMenu->children = $menu->make($active);
                $newMenu->active = $menu->isChildActive;
                $this->isChildActive = ! $this->isChildActive ? $menu->isChildActive : true;

                if (\count($newMenu->children)) {
                    $menu = $newMenu;
                } else {
                    unset($this->list[$key]);
                }

                continue;
            }

            if ($this->activeLink == $menu->route) {
                $menu->active = true;
                $this->isChildActive = true;
            }
        }

        return $this->list;
    }

    public static function generate(string $menuName = 'default', ?string $view = null, $active = null)
    {
        $menuName = \trim($menuName);

        if (isset(self::$menuBag->{$menuName}) && $menu = self::$menuBag->{$menuName}) {
            $menu->make($active);

            $data['sidebar'] = $menu->list;

            $view = $view ?? 'form-tool::layouts.menu';

            return \view($view, $data);
        }

        throw new \InvalidArgumentException('Menu name not found in menu bag: '.$menuName);
    }
}
