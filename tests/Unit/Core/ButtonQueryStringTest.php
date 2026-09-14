<?php

namespace Deep\FormTool\Tests\Unit\Core;

use Deep\FormTool\Core\Button;
use PHPUnit\Framework\TestCase;

class ButtonQueryStringTest extends TestCase
{
    public function test_activity_link_keeps_only_its_parameters_for_each_row(): void
    {
        $button = Button::make('Activities', 'https://example.test/activities-log?module=Students&id={id}')
            ->withQueryString(false);

        foreach ([12, 34] as $id) {
            $rowButton = clone $button;
            $rowButton->process(['{id}', '{query_string}'], [$id, 'quick_status=trash&status=0&page=3&id=99']);
            $this->assertSame('https://example.test/activities-log?module=Students&id='.$id, $rowButton->getFullLink());
        }
    }

    public function test_relative_link_still_resolves_route_without_extra_parameters(): void
    {
        $button = Button::make('View', '/{id}?tab=details')->withQueryString(false);
        $button->process(['{id}', '{crud_url}', '{query_string}'], [12, 'https://example.test/students', 'page=3']);
        $this->assertSame('https://example.test/students/12?tab=details', $button->getFullLink());
    }

    public function test_existing_buttons_still_append_current_query_parameters(): void
    {
        foreach (['/{id}', 'https://example.test/students/{id}'] as $link) {
            $button = Button::make('View', $link);
            $button->process(['{id}', '{crud_url}', '{query_string}'], [12, 'https://example.test/students', 'page=3']);
            $this->assertSame('https://example.test/students/12?page=3', $button->getFullLink());
        }
    }
}
