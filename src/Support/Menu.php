<?php

namespace Biswadeep\FormTool\Support;

use Biswadeep\FormTool\Core\Guard;
use Closure;
use Illuminate\Support\Facades\URL;
use Request;

class Menu
{
    protected static $menuBag = null;
    public $list = [];
    public $activeLink = '';

    protected string $baseURl = '';
    protected bool $isMenuMade = false;

    protected bool $isParent = false;
    protected string $parentLabel = '';
    protected $parentIcon = '';
    protected bool $isChildActive = false;

    private function __construct()
    {
        $this->baseURL = config('form-tool.adminURL', '');
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
     * @param $guardAction optionally you can specify action to guard (Values: null, view, create, edit, delete)
     * @return null
     **/
    public function add(string $route, string $label, string $icon = '', $guardAction = 'view')
    {
        if ($guardAction && Guard::{'has'.$guardAction}($route)) {
            $this->list[] = (object) [
                'href' => URL::to($this->baseURL.'/'.$route),
                'route' => $route,
                'label' => $label,
                'icon' => $icon,
                'active' => false,
                'isParent' => false,
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

        return $this->activeLink = Request::segment(2);
    }

    public function make()
    {
        if ($this->isMenuMade) {
            return $this->list;
        }

        $this->isMenuMade = true;

        $this->activeLink = $this->getActiveLink();

        foreach ($this->list as $key => &$menu) {
            if ($menu instanceof Menu) {
                $newMenu = new \stdClass();
                $newMenu->isParent = true;
                $newMenu->label = $menu->parentLabel;
                $newMenu->icon = $menu->parentIcon;

                // Make the menu and active the parent if any child is active
                $newMenu->childs = $menu->make();
                $newMenu->active = $menu->isChildActive;

                if (\count($newMenu->childs)) {
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

    public static function generate(string $menuName = 'default', string $view = null)
    {
        $menuName = \trim($menuName);

        if (isset(self::$menuBag->{$menuName}) && $menu = self::$menuBag->{$menuName}) {
            $menu->make();

            $data['sideMenu'] = $menu->list;

            $view = $view ?? 'form-tool::components.menu';

            return \view($view, $data);
        }

        throw new \Exception('Menu name not found in menu bag: '.$menuName);
    }
}
