<?php

namespace Deep\FormTool\Tests\Unit\Core;

use Deep\FormTool\Core\InputTypes\TextareaType;
use Deep\FormTool\Core\InputTypes\TextType;
use Tests\TestCase;

class TextInputEscapingTest extends TestCase
{
    public function test_plain_text_is_escaped_when_rendered_and_kept_raw_for_data_and_exports(): void
    {
        $value = '\"><script>alert("x")</script> & </textarea> Café';

        foreach ($this->inputs() as $input) {
            $input->setDbField('title');
            $input->setValue($value);

            $this->assertStringContainsString(e($value), $input->getHTML());
            $this->assertStringNotContainsString('<script>', $input->getHTML());
            $this->assertSame(e($value), $input->getTableValue());
            $this->assertSame($value, $input->getValue());
            $this->assertSame($value, $input->getExportValue($value));

            $html = $input->getHTMLMultiple('rows', 0, $value);
            $this->assertStringContainsString(e($value), $html);
            $this->assertStringNotContainsString('<script>', $html);

            $input->setValue(null);
            $this->assertSame('', $input->getTableValue());
        }
    }

    private function inputs(): array
    {
        return [
            new class extends TextType
            {
                protected function htmlParentDiv($input): string
                {
                    return $input;
                }
            },
            new class extends TextareaType
            {
                protected function htmlParentDiv($input): string
                {
                    return $input;
                }
            },
        ];
    }
}
