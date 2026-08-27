<?php

namespace Deep\FormTool\Tests\Unit\Core\InputTypes;

require_once dirname(__DIR__, 3).'/TestCase.php';

use Deep\FormTool\Core\BluePrint;
use Deep\FormTool\Tests\TestCase;

class CheckboxTypeActionLoggerTest extends TestCase
{
    public function test_checkbox_update_logging_does_not_require_a_first_option(): void
    {
        $input = (new BluePrint())->checkbox('enabled', 'Enabled')->setValue('1');

        $this->assertSame([
            'type' => 'text',
            'data' => ['No', 'Yes'],
        ], $input->getLoggerValue('update', '0'));
    }
}
