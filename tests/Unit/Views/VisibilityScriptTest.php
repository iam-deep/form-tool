<?php

namespace Deep\FormTool\Tests\Unit\Views;

use PHPUnit\Framework\TestCase;

class VisibilityScriptTest extends TestCase
{
    public function test_it_does_not_restore_native_required_on_chosen_fields(): void
    {
        $script = file_get_contents(
            __DIR__.'/../../../src/views/form/scripts/global.php'
        );

        $this->assertStringContainsString(
            "let supportsNativeRequired = !target.hasClass('chosen');",
            $script
        );
        $this->assertStringContainsString(
            "target.prop('required', shouldShow && isOriginallyRequired && supportsNativeRequired);",
            $script
        );
    }
}
