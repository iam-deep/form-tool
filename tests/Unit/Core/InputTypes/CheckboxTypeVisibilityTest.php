<?php

namespace Deep\FormTool\Tests\Unit\Core\InputTypes;

use Deep\FormTool\Core\InputTypes\CheckboxType;
use Deep\FormTool\Core\InputTypes\Common\IVisibilityController;
use Deep\FormTool\Exceptions\FormToolException;
use PHPUnit\Framework\TestCase;

class CheckboxTypeVisibilityTest extends TestCase
{
    public function test_it_applies_one_trigger_value_to_multiple_target_fields(): void
    {
        $input = new TestCheckboxType();

        $this->assertInstanceOf(IVisibilityController::class, $input);

        $input->show(['fieldA', 'fieldB'], 1);

        $this->assertSame([
            'fieldA' => [
                'action' => 'show',
                'values' => ['1'],
            ],
            'fieldB' => [
                'action' => 'show',
                'values' => ['1'],
            ],
        ], $input->getVisibilityRules());
    }

    public function test_it_rejects_an_array_trigger_value(): void
    {
        $this->expectException(FormToolException::class);
        $this->expectExceptionMessage('Checkbox visibility trigger value must be scalar or null.');

        (new TestCheckboxType())->hide('details', [0, 1]);
    }

    public function test_it_accumulates_rules_and_rejects_conflicting_actions(): void
    {
        $input = new TestCheckboxType();
        $input->hide('details', 0)->hide('details', false);

        $this->assertSame(['0'], $input->getVisibilityRules()['details']['values']);

        $this->expectException(FormToolException::class);
        $input->show('details', 1);
    }

    public function test_it_supplies_the_default_unchecked_value_before_validation(): void
    {
        $input = (new TestCheckboxType())->show('details', 1);

        $this->assertSame('0', $input->beforeValidation(null));
        $this->assertNull($input->beforeValidation('1'));
    }

    public function test_it_supplies_a_custom_unchecked_value_before_validation(): void
    {
        $input = (new TestCheckboxType())
            ->values('enabled', 'disabled')
            ->show('details', 'enabled');

        $this->assertSame('disabled', $input->beforeValidation(null));
        $this->assertNull($input->beforeValidation('enabled'));
    }

    public function test_it_renders_visibility_and_unchecked_value_attributes(): void
    {
        $input = (new TestCheckboxType())
            ->values('enabled', 'disabled')
            ->show(['fieldA', 'fieldB'], 'enabled');

        $attributes = $input->visibilityAttributes();

        $this->assertStringContainsString('data-form-tool-visibility="{&quot;fieldA&quot;', $attributes);
        $this->assertStringContainsString('data-form-tool-unchecked-value="disabled"', $attributes);
    }

    public function test_it_rejects_a_multiple_option_checkbox_controller(): void
    {
        $input = (new TestCheckboxType())->multiple()->show('details', 1);

        $this->expectException(FormToolException::class);
        $this->expectExceptionMessage('Visibility rules require a single-value controlling checkbox.');

        $input->visibilityAttributes();
    }

    public function test_it_stores_a_custom_message_for_a_checkbox_visibility_target(): void
    {
        $input = (new TestCheckboxType())->show(
            'details',
            1,
            ['details' => 'Details are required.']
        );

        $this->assertSame(
            'Details are required.',
            $input->getVisibilityRules()['details']['message']
        );
    }
}

class TestCheckboxType extends CheckboxType
{
    public function visibilityAttributes(): string
    {
        return $this->getVisibilityAttributes();
    }
}
