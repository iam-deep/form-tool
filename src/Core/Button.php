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
    private bool $active = true;

    private ?string $processedLink = null;
    private ?string $processedHtml = null;

    public function __construct(?string $name = null, string $link = null, $guard = null)
    {
        $this->name = trim($name);
        $this->link = trim($link);
        $this->guard = $guard;

        return $this;
    }

    public static function make(string $name = null, string $link = null, $guard = null): Button
    {
        $button = new Button($name, $link, $guard);
        $button->type = 'link';

        if (! self::isGuard($guard)) {
            $button->active = false;
        }

        return $button;
    }

    public static function makeView(string $name = 'View', string $link = '/{id}', $guard = 'view'): Button
    {
        $button = self::make($name, $link, strtolower($guard));
        $button->icon('<i class="'.config('form-tool.icons.view').'"></i>');

        if (! self::isGuard($guard)) {
            $button->active = false;
        }

        return $button;
    }

    public static function makeEdit(string $name = 'Edit', string $link = '/{id}/edit', $guard = 'edit'): Button
    {
        $button = self::make($name, $link, strtolower($guard));
        $button->icon('<i class="'.config('form-tool.icons.edit').'"></i>');

        if (! self::isGuard($guard)) {
            $button->active = false;
        }

        return $button;
    }

    public static function makeDelete(string $name = 'Delete', string $link = '/{id}', $guard = 'delete'): Button
    {
        $button = new Button($name, $link, strtolower($guard));

        if (! self::isGuard($guard)) {
            $button->active = false;
        }

        $data['button'] = (object) [
            'id' => '{crud_name}_delete_{id}',
            'action' => '{crud_url}'.$link.'?{query_string}',
            'name' => $name,
        ];

        $button->html(\view('form-tool::list.button_delete', $data)->render());

        return $button;
    }

    public static function makeHtml(string $html, string $guard = null): Button
    {
        $button = (new Button())->html($html);
        if (! self::isGuard($guard)) {
            $button->active = false;
        }

        return $button;
    }

    public static function makeDivider(string $guard = null): Button
    {
        return (new Button())->divider()->guard($guard);
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

    public function guard(?string $guard): Button
    {
        if (! self::isGuard($guard)) {
            $this->active = false;
        }

        $this->guard = strtolower($guard);

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
            if (false !== strpos($this->link, 'http')) {
                $link = $this->link;
                $link .= strpos($this->link, '?') ? '&' : '?';

                $this->processedLink = str_replace($search, $replace, $link.'{query_string}');
            } else {
                $this->processedLink = str_replace($search, $replace, '{crud_url}'.$this->link.'?{query_string}');
            }
        }

        if ($this->raw) {
            $this->processedHtml = str_replace($search, $replace, $this->raw);
        }
    }

    private static function isGuard($guard)
    {
        if (! is_null($guard)) {
            if (is_string($guard)) {
                return Guard::can(strtolower($guard));
            } else {
                return $guard;
            }
        }

        return true;
    }
}
