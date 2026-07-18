<?php

namespace Deep\FormTool\Tests\Unit\Core\InputTypes;

use Deep\FormTool\Core\BluePrint;
use Deep\FormTool\Exceptions\FormToolException;
use PHPUnit\Framework\TestCase;

class SelectTypeVisibilityTest extends TestCase
{
    public function test_it_normalizes_fields_and_trigger_values(): void
    {
        $input = (new BluePrint())->select('status');

        $input->hide(['reason', 'note'], [1, 'pending'], true);

        $this->assertSame([
            'reason' => [
                'action' => 'hide',
                'values' => ['1', 'pending'],
                'isRequiredOnShow' => true,
            ],
            'note' => [
                'action' => 'hide',
                'values' => ['1', 'pending'],
                'isRequiredOnShow' => true,
            ],
        ], $input->getVisibilityRules());
    }

    public function test_it_accumulates_rules_and_merges_values_for_the_same_target(): void
    {
        $input = (new BluePrint())->select('status');

        $input
            ->hide('reason', 'active')
            ->hide('reason', ['pending', 'active'], true)
            ->show(['approvedBy', 'approvedAt'], ['approved', 'completed'], true);

        $this->assertSame([
            'reason' => [
                'action' => 'hide',
                'values' => ['active', 'pending'],
                'isRequiredOnShow' => true,
            ],
            'approvedBy' => [
                'action' => 'show',
                'values' => ['approved', 'completed'],
                'isRequiredOnShow' => true,
            ],
            'approvedAt' => [
                'action' => 'show',
                'values' => ['approved', 'completed'],
                'isRequiredOnShow' => true,
            ],
        ], $input->getVisibilityRules());
    }

    public function test_it_rejects_show_and_hide_rules_for_the_same_target(): void
    {
        $input = (new BluePrint())->select('status');
        $input->hide('reason', 'active');

        $this->expectException(FormToolException::class);
        $this->expectExceptionMessage('Field "reason" cannot have both show and hide visibility rules.');

        $input->show('reason', 'inactive');
    }

    public function test_it_rejects_empty_target_fields(): void
    {
        $this->expectException(FormToolException::class);
        $this->expectExceptionMessage('Visibility fields cannot be empty.');

        (new BluePrint())->select('status')->hide([''], 'active');
    }

    public function test_it_rejects_non_scalar_trigger_values(): void
    {
        $this->expectException(FormToolException::class);
        $this->expectExceptionMessage('Visibility values must be scalar or null.');

        (new BluePrint())->select('status')->hide('reason', [['active']]);
    }

    public function test_it_normalizes_null_as_the_empty_select_value(): void
    {
        $input = (new BluePrint())->select('status')->show('reason', null);

        $this->assertSame([''], $input->getVisibilityRules()['reason']['values']);
    }
}
