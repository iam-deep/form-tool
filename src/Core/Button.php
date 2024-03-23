<?php

namespace Deep\FormTool\Core;

class Button
{
    /**
     * $type desired values are (link, html, divider).
     */
    private string $type = 'link';
    private ?string $name = null;
    private ?string $icon = null;
    private ?string $link = null;

    private string $raw = '';

    private ?string $guard = null;
    private ?string $guardRoute = '';
    private bool $active = true;

    private ?string $processedLink = null;
    private ?string $processedHtml = null;

    private function __construct(?string $name = null, string $link = null, $guard = null, $guardRoute = '')
    {
        $this->name = trim($name);
        $this->link = trim($link);
        $this->guard = $guard;
        $this->guardRoute = $guardRoute;

        if (! self::isGuard($guard, $guardRoute)) {
            $this->active = false;
        }

        return $this;
    }

    public static function make(string $name = null, string $link = null, $guard = null, $guardRoute = ''): Button
    {
        $button = new Button($name, $link, $guard, $guardRoute);
        $button->type = 'link';

        return $button;
    }

    public static function makeView(string $name = 'View', string $link = '/{id}', $guard = 'view', $guardRoute = ''): Button
    {
        $button = self::make($name, $link, strtolower($guard), $guardRoute);
        $button->type = 'link';
        $button->icon('<i class="'.config('form-tool.icons.view').'"></i>');

        // if (! self::isGuard($guard, $guardRoute)) {
        //     $button->active = false;
        // }

        return $button;
    }

    public static function makeEdit(string $name = 'Edit', string $link = '/{id}/edit', $guard = 'edit', $guardRoute = ''): Button
    {
        $button = self::make($name, $link, strtolower($guard), $guardRoute);
        $button->type = 'link';
        $button->icon('<i class="'.config('form-tool.icons.edit').'"></i>');

        // if (! self::isGuard($guard, $guardRoute)) {
        //     $button->active = false;
        // }

        return $button;
    }

    public static function makeDelete(string $name = 'Delete', string $link = '/{id}', $guard = 'delete', $guardRoute = ''): Button
    {
        $button = new Button($name, $link, strtolower($guard), $guardRoute);
        $button->type = 'link';

        // if (! self::isGuard($guard, $guardRoute)) {
        //     $button->active = false;
        // }

        $data['button'] = (object) [
            'id' => '{crud_name}_delete_{id}',
            'action' => '{crud_url}'.$link.'?{query_string}',
            'name' => $name,
        ];

        $button->html(\view('form-tool::list.button_delete', $data)->render());

        return $button;
    }

    public static function makeHtml(string $html, string $guard = null, $guardRoute = ''): Button
    {
        $button = (new Button(null, null, $guard, $guardRoute))->html($html);
        // if (! self::isGuard($guard, $guardRoute)) {
        //     $button->active = false;
        // }

        return $button;
    }

    public static function makeDivider(string $guard = null, $guardRoute = ''): Button
    {
        return (new Button())->divider()->guard($guard, $guardRoute);
    }

    public function name(string $name): Button
    {
        $this->name = trim($name);

        return $this;
    }

    public function icon(string $icon): Button
    {
        $this->icon = $icon;

        return $this;
    }

    public function link(string $link): Button
    {
        $this->link = trim($link);

        return $this;
    }

    public function blank(): Button
    {
        $this->raw .= 'target="_blank" ';

        return $this;
    }

    public function guard(?string $guard, $guardRoute = ''): Button
    {
        if (! self::isGuard($guard, $guardRoute)) {
            $this->active = false;
        }

        $this->guard = strtolower($guard);
        $this->guardRoute = $guardRoute;

        return $this;
    }

    public function divider(): Button
    {
        $this->type = 'divider';

        return $this;
    }

    public function raw(string $raw): Button
    {
        $this->raw .= $raw.' ';

        return $this;
    }

    public function html(string $html): Button
    {
        $this->type = 'html';
        $this->raw($html);

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function getGuard(): ?string
    {
        return $this->guard;
    }

    public function getGuardRoute(): ?string
    {
        return $this->guardRoute;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getRaw(): string
    {
        return $this->raw;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function isLink(): bool
    {
        return $this->type == 'link';
    }

    public function isHtml(): bool
    {
        return $this->type == 'html';
    }

    public function isDivider(): bool
    {
        return $this->type == 'divider';
    }

    public function getFullLink()
    {
        return $this->processedLink;
    }

    public function getHtml(string $class = null)
    {
        return \str_replace('{class}', $class, $this->processedHtml);
    }

    public function process($search, $replace)
    {
        if ($this->link) {
            $link = $this->link;
            if (false === strpos($link, ':')) {
                // Relative link

                $link .= strpos($link, '?') ? '&' : '?';
                $link = '{crud_url}'.$link.'{query_string}';
            } elseif (false !== strpos($link, 'http')) {
                // http link

                $link .= strpos($link, '?') ? '&' : '?';
                $link = $link.'{query_string}';
            }

            $this->processedLink = str_replace($search, $replace, $link);
        }

        if ($this->raw) {
            $this->processedHtml = str_replace($search, $replace, $this->raw);
        }
    }

    private static function isGuard($guard, $route = '')
    {
        if (! is_null($guard)) {
            if (is_string($guard)) {
                return Guard::can(strtolower($guard), $route);
            } else {
                return $guard;
            }
        }

        return true;
    }
}
