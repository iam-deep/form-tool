<?php

namespace Deep\FormTool\Tests\Unit\Core;

use Deep\FormTool\Core\BluePrint;
use Deep\FormTool\Core\InputTypes\BaseInputType;
use Deep\FormTool\Core\InputTypes\EditorType;
use Deep\FormTool\Core\InputTypes\TextareaType;
use Deep\FormTool\Core\InputTypes\TextType;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TextLengthValidationTest extends TestCase
{
    public function test_defaults_accept_the_boundary_and_reject_longer_text_on_create_and_update(): void
    {
        foreach ([[new TextType(), 250], [new TextareaType(), 500], [new EditorType(), 65500]] as [$input, $limit]) {
            foreach (['store', 'update'] as $action) {
                $this->assertValid($input, str_repeat('a', $limit), true, $action);
                $this->assertValid($input, str_repeat('a', $limit + 1), false, $action);
                $this->assertValid($input, null, true, $action);
            }
        }
    }

    public function test_fluent_limits_and_validation_rules_override_each_other_in_call_order(): void
    {
        foreach ([new TextType(), new TextareaType(), new EditorType()] as $input) {
            $this->assertSame($input, $input->min(2)->max(70000));
            $this->assertValid($input, 'a', false);
            $this->assertValid($input, 'ab', true);
            $this->assertValid($input, str_repeat('a', 70000), true);
            $this->assertValid($input, str_repeat('a', 70001), false);

            $input->validations('min:3|max:4', ['max' => 'Too long.']);
            $this->assertValid($input, 'ab', false);
            $this->assertValid($input, 'abcd', true);
            $this->assertValid($input, 'abcde', false);

            $input->min(0)->max(5)->max(6);
            $this->assertValid($input, 'ab', true);
            $this->assertValid($input, 'abcdef', true);
            $this->assertValid($input, 'abcdefg', false);
            $rules = $input->getValidations('store');
            $this->assertSame(['min:0', 'max:6'], array_values(array_filter($rules,
                fn ($rule) => is_string($rule) && preg_match('/^(min|max):/', $rule))));
            $this->assertSame($rules, $input->getValidations('store'));
        }
    }

    public function test_array_rules_replacement_custom_messages_and_required_are_preserved(): void
    {
        foreach ([new TextType(), new TextareaType(), new EditorType()] as $input) {
            $input->setDbField('content');
            $input->max(3)->validations(['required', 'min:2', 'max:10'], ['max' => 'Custom maximum.'], true);
            $this->assertValid($input, null, false);
            $this->assertValid($input, '1234567890', true);
            $this->assertValid($input, '12345678901', false);
            $this->assertSame('Custom maximum.', $input->getValidationMessages()['content.max']);
        }
    }

    public function test_text_counts_characters_but_editor_default_counts_stored_utf8_bytes_and_entities(): void
    {
        $this->assertValid(new TextType(), str_repeat('界', 250), true);
        $this->assertValid(new TextType(), str_repeat('界', 251), false);

        $editor = new EditorType();
        $this->assertValid($editor, str_repeat('界', 21833), true);
        $this->assertValid($editor, str_repeat('界', 21834), false);
        $this->assertValid($editor, str_repeat('&', 13100), true);
        $this->assertValid($editor, str_repeat('&', 13101), false);
        $this->assertValid($editor->max(70000), str_repeat('&', 14000), true);
    }

    public function test_number_and_email_inputs_do_not_receive_the_plain_text_default(): void
    {
        $blueprint = new BluePrint();
        foreach ([$blueprint->number('amount'), $blueprint->email('email')] as $input) {
            $this->assertNotContains('max:250', $input->getValidations('store'));
        }
    }

    public function test_minimum_alone_keeps_default_maximum_and_replacement_can_restore_it(): void
    {
        $input = (new TextType())->min(2);
        $this->assertValid($input, str_repeat('a', 251), false);
        $input->max(300);
        $this->assertValid($input, str_repeat('a', 251), true);
        $input->validations(['required'], [], true);
        $this->assertValid($input, str_repeat('a', 251), false);
        $this->assertValid($input, 'a', true);
    }

    public function test_negative_fluent_limits_are_rejected(): void
    {
        foreach ([new TextType(), new TextareaType(), new EditorType()] as $input) {
            foreach (['min', 'max'] as $method) {
                try {
                    $input->{$method}(-1);
                    $this->fail('A negative limit was accepted.');
                } catch (\InvalidArgumentException $exception) {
                    $this->assertStringContainsString('zero or greater', $exception->getMessage());
                }
            }
        }
    }

    private function assertValid(BaseInputType $input, ?string $value, bool $expected, string $action = 'store'): void
    {
        $validator = Validator::make(['content' => $value], ['content' => $input->getValidations($action)]);
        $this->assertSame($expected, $validator->passes(), get_class($input).' '.$action.' '.json_encode($validator->errors()->all()));
    }
}
