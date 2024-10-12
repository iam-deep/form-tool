<?php

namespace Deep\FormTool\Core\InputTypes\Common;

/**
 * For Select.
 *
 * @method static \Deep\FormTool\Core\InputTypes\SelectType first(string $firstOption, $firstValue = '')
 * @method static \Deep\FormTool\Core\InputTypes\SelectType multiple()
 */
interface ICustomType
{
    public function getHTML();
}
