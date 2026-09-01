<?php

namespace Deep\FormTool\Tests\Unit\Core\InputTypes;

require_once dirname(__DIR__, 3).'/TestCase.php';

use Deep\FormTool\Core\Doc;
use Deep\FormTool\Core\InputTypes\SelectType;
use Deep\FormTool\Tests\TestCase;
use ReflectionProperty;

class SelectTypePluginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetDocAssets();
    }

    protected function tearDown(): void
    {
        $this->resetDocAssets();

        parent::tearDown();
    }

    public function test_virtual_plugin_registers_assets_and_initializer(): void
    {
        $select = (new InspectableSelectType())->plugin('virtual');

        $select->setPlugin();

        $this->assertContains('virtual-select', $select->exposeClasses());
        $this->assertNotContains('form-control', $select->exposeClasses());
        $this->assertContains(
            'assets/form-tool/plugins/virtual-select/virtual-select.min.css',
            $this->docAsset('cssLink')
        );
        $this->assertContains(
            'assets/form-tool/plugins/virtual-select/virtual-select.min.js',
            $this->docAsset('jsLink')
        );
        $this->assertStringContainsString('VirtualSelect.init', Doc::getJs());
    }

    public function test_virtual_plugin_registers_multiple_after_add_initializer(): void
    {
        (new InspectableSelectType())->plugin('virtual')->setPlugin(true);

        $this->assertStringContainsString(
            'formToolInitVirtualSelect',
            Doc::getJsGroup('multiple_after_add')
        );
    }

    public function test_multiple_does_not_override_an_explicit_virtual_plugin(): void
    {
        $select = (new InspectableSelectType())->plugin('virtual')->multiple();

        $select->setPlugin();

        $this->assertContains('virtual-select', $select->exposeClasses());
        $this->assertNotContains('chosen', $select->exposeClasses());
    }

    private function docAsset(string $propertyName): array
    {
        $property = new ReflectionProperty(Doc::class, $propertyName);

        return $property->getValue();
    }

    private function resetDocAssets(): void
    {
        foreach (['cssLink', 'jsLink', 'css', 'js', 'jsGroup'] as $propertyName) {
            $property = new ReflectionProperty(Doc::class, $propertyName);
            $property->setValue(null, []);
        }
    }
}

final class InspectableSelectType extends SelectType
{
    public function exposeClasses(): array
    {
        return $this->classes;
    }
}
