<?php

namespace Deep\FormTool\Tests\Unit\Core;

use Deep\FormTool\Core\InputTypes\SelectType;
use Tests\TestCase;

class VirtualSelectFilterTest extends TestCase
{
    public function test_multiple_filter_restores_each_selected_value_after_reload(): void
    {
        foreach (['["1","2"]', ['1', '2'], '1,2', '[]', null] as $value) {
            $field = (new SelectType())->options([1 => 'Class 1', 2 => 'Class 2'])->multiple()->plugin('virtual');
            $field->setDbField('classIds')->setValue($value);
            $dom = new \DOMDocument();
            @$dom->loadHTML($field->getFilterHTML());
            $selected = json_decode($dom->getElementById('classIds')->getAttribute('data-selected-value'), true);
            $this->assertSame(in_array($value, ['[]', null], true) ? [] : ['1', '2'], $selected);
        }
    }
}
