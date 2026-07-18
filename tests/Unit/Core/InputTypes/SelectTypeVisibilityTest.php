<?php

namespace Deep\FormTool\Tests\Unit\Core\InputTypes;

use Deep\FormTool\Core\BluePrint;
use Deep\FormTool\Core\InputTypes\Common\IVisibilityController;
use Deep\FormTool\Exceptions\FormToolException;
use PHPUnit\Framework\TestCase;

class SelectTypeVisibilityTest extends TestCase
{
    public function test_it_normalizes_fields_and_trigger_values(): void
    {
        $input = (new BluePrint())->select('status');

        $this->assertInstanceOf(IVisibilityController::class, $input);

        $input->hide(['reason', 'note'], [1, 'pending']);

        $this->assertSame([
            'reason' => [
                'action' => 'hide',
                'values' => ['1', 'pending'],
            ],
            'note' => [
                'action' => 'hide',
                'values' => ['1', 'pending'],
            ],
        ], $input->getVisibilityRules());
    }

    public function test_it_accumulates_rules_and_merges_values_for_the_same_target(): void
    {
        $input = (new BluePrint())->select('status');

        $input
            ->hide('reason', 'active')
            ->hide('reason', ['pending', 'active'])
            ->show(['approvedBy', 'approvedAt'], ['approved', 'completed']);

        $this->assertSame([
            'reason' => [
                'action' => 'hide',
                'values' => ['active', 'pending'],
            ],
            'approvedBy' => [
                'action' => 'show',
                'values' => ['approved', 'completed'],
            ],
            'approvedAt' => [
                'action' => 'show',
                'values' => ['approved', 'completed'],
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

    public function test_it_stores_custom_messages_only_for_mapped_target_fields(): void
    {
        $input = (new BluePrint())->select('scope')->hide(
            ['sections', 'note'],
            1,
            ['sections' => 'Please select at least one section.']
        );

        $this->assertSame(
            'Please select at least one section.',
            $input->getVisibilityRules()['sections']['message']
        );
        $this->assertArrayNotHasKey('message', $input->getVisibilityRules()['note']);
    }

    public function test_it_rejects_a_message_for_a_field_outside_the_current_targets(): void
    {
        $this->expectException(FormToolException::class);
        $this->expectExceptionMessage(
            'Visibility message field "unknown" must be included in the target fields.'
        );

        (new BluePrint())->select('scope')->hide(
            'sections',
            1,
            ['unknown' => 'Invalid mapping.']
        );
    }

    public function test_it_rejects_an_empty_visibility_message(): void
    {
        $this->expectException(FormToolException::class);
        $this->expectExceptionMessage(
            'Visibility message for field "sections" cannot be empty.'
        );

        (new BluePrint())->select('scope')->hide(
            'sections',
            1,
            ['sections' => '   ']
        );
    }

    public function test_it_reuses_the_same_message_for_accumulated_rules(): void
    {
        $input = (new BluePrint())->select('scope')
            ->hide('sections', 1, ['sections' => 'Select sections.'])
            ->hide('sections', 2, ['sections' => 'Select sections.']);

        $this->assertSame(['1', '2'], $input->getVisibilityRules()['sections']['values']);
        $this->assertSame(
            'Select sections.',
            $input->getVisibilityRules()['sections']['message']
        );
    }

    public function test_it_rejects_conflicting_messages_for_the_same_target(): void
    {
        $input = (new BluePrint())->select('scope')
            ->hide('sections', 1, ['sections' => 'Select sections.']);

        $this->expectException(FormToolException::class);
        $this->expectExceptionMessage(
            'Visibility message for field "sections" conflicts with an existing message.'
        );

        $input->hide('sections', 2, ['sections' => 'Choose sections.']);
    }
}
