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
        $bluePrint->select('status')->show('details', ['approved', 'completed'], true);
        $bluePrint->text('details');

        $rules = [
            'status' => ['required' => 'nullable'],
            'details' => ['required' => 'nullable'],
        ];

        $this->applyVisibilityRules($bluePrint, $rules);

        $this->assertContains('required_if:status,approved,completed', $rules['details']);
        $this->assertContains('nullable', $rules['details']);
    }

    public function test_it_adds_required_unless_for_hide_rules(): void
    {
        $bluePrint = new BluePrint();
        $bluePrint->select('scope')->hide('sections', [0, 1], true);
        $bluePrint->select('sections');

        $rules = [
            'scope' => ['required' => 'nullable'],
            'sections' => ['required' => 'nullable'],
        ];

        $this->applyVisibilityRules($bluePrint, $rules);

        $this->assertContains('required_unless:scope,0,1', $rules['sections']);
        $this->assertContains('nullable', $rules['sections']);
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
        $bluePrint->select('status')->show('details', 'approved', true);

        $rules = ['status' => ['required' => 'nullable']];

        $this->expectException(FormToolException::class);
        $this->expectExceptionMessage(
            'Visibility target field "details" was not found for controller "status".'
        );

        $this->applyVisibilityRules($bluePrint, $rules);
    }

    private function applyVisibilityRules(BluePrint $bluePrint, array &$rules): void
    {
        $reflection = new ReflectionClass(Form::class);
        $form = $reflection->newInstanceWithoutConstructor();

        $bluePrintProperty = $reflection->getProperty('bluePrint');
        $bluePrintProperty->setAccessible(true);
        $bluePrintProperty->setValue($form, $bluePrint);

        $method = $reflection->getMethod('applyVisibilityValidationRules');
        $method->setAccessible(true);
        $method->invokeArgs($form, [&$rules]);
    }
}
