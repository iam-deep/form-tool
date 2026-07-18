<?php

namespace Deep\FormTool\Tests\Unit\Core;

use Deep\FormTool\Core\BluePrint;
use Deep\FormTool\Core\Form;
use Deep\FormTool\Exceptions\FormToolException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class FormVisibilityValidationTest extends TestCase
{
    public function test_it_adds_required_if_for_show_rules(): void
    {
        $bluePrint = new BluePrint();
        $bluePrint->select('status')->show('details', ['approved', 'completed']);
        $bluePrint->text('details')->required();

        $rules = [
            'status' => ['required' => 'nullable'],
            'details' => ['required' => 'required'],
        ];

        $this->applyVisibilityRules($bluePrint, $rules);

        $this->assertContains('required_if:status,approved,completed', $rules['details']);
    }

    public function test_it_adds_required_unless_for_hide_rules(): void
    {
        $bluePrint = new BluePrint();
        $bluePrint->select('scope')->hide('sections', [0, 1]);
        $bluePrint->select('sections')->required();

        $rules = [
            'scope' => ['required' => 'nullable'],
            'sections' => ['required' => 'required'],
        ];

        $this->applyVisibilityRules($bluePrint, $rules);

        $this->assertContains('required_unless:scope,0,1', $rules['sections']);
    }

    public function test_it_converts_existing_required_to_conditional_required(): void
    {
        $bluePrint = new BluePrint();
        $bluePrint->select('scope')->hide('sections', 1);
        $bluePrint->select('sections')->required();

        $rules = [
            'scope' => ['required' => 'nullable'],
            'sections' => ['required' => 'required'],
        ];

        $this->applyVisibilityRules($bluePrint, $rules);

        $this->assertNotContains('required', $rules['sections']);
        $this->assertContains('required_unless:scope,1', $rules['sections']);
    }

    public function test_it_throws_when_a_visibility_target_is_missing(): void
    {
        $bluePrint = new BluePrint();
        $bluePrint->select('status')->show('details', 'approved');

        $rules = ['status' => ['required' => 'nullable']];

        $this->expectException(FormToolException::class);
        $this->expectExceptionMessage(
            'Visibility target field "details" was not found for controller "status".'
        );

        $this->applyVisibilityRules($bluePrint, $rules);
    }

    public function test_it_resolves_checkbox_visibility_rules(): void
    {
        $bluePrint = new BluePrint();
        $bluePrint->checkbox('status')->values('enabled', 'disabled')
            ->show('details', 'disabled');
        $bluePrint->text('details')->required();

        $rules = [
            'status' => ['required' => 'nullable'],
            'details' => ['required' => 'required'],
        ];

        $this->applyVisibilityRules($bluePrint, $rules);

        $this->assertContains('required_if:status,disabled', $rules['details']);
    }

    public function test_it_keeps_an_optional_visibility_target_optional(): void
    {
        $bluePrint = new BluePrint();
        $bluePrint->select('status')->show('details', 'approved');
        $bluePrint->text('details');

        $rules = [
            'status' => ['required' => 'nullable'],
            'details' => ['required' => 'nullable'],
        ];

        $this->applyVisibilityRules($bluePrint, $rules);

        $this->assertSame(['required' => 'nullable'], $rules['details']);
    }

    public function test_it_registers_a_custom_message_for_a_hide_rule(): void
    {
        $bluePrint = new BluePrint();
        $bluePrint->select('scope')->hide(
            'sections',
            1,
            ['sections' => 'Please select at least one section.']
        );
        $bluePrint->select('sections')->required();

        $rules = [
            'scope' => ['required' => 'nullable'],
            'sections' => ['required' => 'required'],
        ];
        $messages = [];

        $this->applyVisibilityRules($bluePrint, $rules, $messages);

        $this->assertSame(
            'Please select at least one section.',
            $messages['sections.required_unless']
        );
    }

    public function test_it_registers_a_custom_message_for_a_show_rule(): void
    {
        $bluePrint = new BluePrint();
        $bluePrint->select('status')->show(
            'details',
            'approved',
            ['details' => 'Approval details are required.']
        );
        $bluePrint->text('details')->required();

        $rules = [
            'status' => ['required' => 'nullable'],
            'details' => ['required' => 'required'],
        ];
        $messages = [];

        $this->applyVisibilityRules($bluePrint, $rules, $messages);

        $this->assertSame(
            'Approval details are required.',
            $messages['details.required_if']
        );
    }

    public function test_it_registers_a_custom_message_for_a_checkbox_rule(): void
    {
        $bluePrint = new BluePrint();
        $bluePrint->checkbox('status')->show(
            'details',
            1,
            ['details' => 'Details are required.']
        );
        $bluePrint->text('details')->required();

        $rules = [
            'status' => ['required' => 'nullable'],
            'details' => ['required' => 'required'],
        ];
        $messages = [];

        $this->applyVisibilityRules($bluePrint, $rules, $messages);

        $this->assertSame('Details are required.', $messages['details.required_if']);
    }

    public function test_it_does_not_register_a_custom_message_for_an_optional_target(): void
    {
        $bluePrint = new BluePrint();
        $bluePrint->select('status')->show(
            'details',
            'approved',
            ['details' => 'Details are required.']
        );
        $bluePrint->text('details');

        $rules = [
            'status' => ['required' => 'nullable'],
            'details' => ['required' => 'nullable'],
        ];
        $messages = [];

        $this->applyVisibilityRules($bluePrint, $rules, $messages);

        $this->assertArrayNotHasKey('details.required_if', $messages);
    }

    private function applyVisibilityRules(
        BluePrint $bluePrint,
        array &$rules,
        ?array &$messages = null
    ): void {
        $messages ??= [];

        $reflection = new ReflectionClass(Form::class);
        $form = $reflection->newInstanceWithoutConstructor();

        $bluePrintProperty = $reflection->getProperty('bluePrint');
        $bluePrintProperty->setAccessible(true);
        $bluePrintProperty->setValue($form, $bluePrint);

        $method = $reflection->getMethod('applyVisibilityValidationRules');
        $method->setAccessible(true);
        $method->invokeArgs($form, [&$rules, &$messages]);
    }
}
