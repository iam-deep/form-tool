<?php

namespace Deep\FormTool\Tests\Unit\Core\InputTypes;

require_once dirname(__DIR__, 3).'/TestCase.php';

use Deep\FormTool\Core\BluePrint;
use Deep\FormTool\Tests\TestCase;

class SelectTypeActionLoggerTest extends TestCase
{
    public function test_null_and_empty_first_option_values_are_unchanged(): void
    {
        $input = (new BluePrint())
            ->select('calculationType', 'Calculation Type')
            ->options(['disable' => 'Disable'])
            ->first('Default')
            ->setValue(null);

        $this->assertSame('', $input->getLoggerValue('update', ''));
    }

    public function test_real_change_from_first_option_is_logged(): void
    {
        $input = (new BluePrint())
            ->select('calculationType', 'Calculation Type')
            ->options(['disable' => 'Disable'])
            ->first('Default')
            ->setValue('disable');

        $this->assertSame([
            'type' => 'text',
            'data' => ['Default', 'Disable'],
        ], $input->getLoggerValue('update', ''));
    }
}
