<?php

namespace Deep\FormTool\Core\InputTypes;

use Deep\FormTool\Core\InputTypes\Common\InputType;

class HtmlType extends BaseInputType
{
    public int $type = InputType::HTML;
    public string $typeInString = 'html';

    protected ?string $data = '';

    public function getHTML()
    {
        return $this->label;
    }
}
