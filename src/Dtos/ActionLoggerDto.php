<?php

namespace Deep\FormTool\Dtos;

use Deep\FormTool\Core\ActionLogger;
use Deep\FormTool\Core\BluePrint;
use Deep\FormTool\Enums\ActionLoggerEnum;

class ActionLoggerDto
{
    private $items = [];

    private $bluePrint = null;
    private $bluePrintData = null;
    private bool $isOnlyForAdmin = false;

    public function __construct(
        public readonly ActionLoggerEnum $action,
        public readonly string $moduleTitle,
        public ?string $description = null,
        public ?string $route = null,
        public ?string $fullPath = null,
        public ?string $id = null,
        public ?string $token = null,
        public ?string $extraData = null,
    ) {
    }

    public function getJsonData(): ?string
    {
        if ($this->bluePrint) {
            return json_encode(ActionLogger::getCreateData($this->bluePrint, $this->bluePrintData));
        }

        if (! $this->items) {
            return null;
        }

        return json_encode(['data' => $this->items]);
    }

    public function onlyForAdmin(bool $flag = true): self
    {
        $this->isOnlyForAdmin = $flag;

        return $this;
    }

    public function isOnlyForAdmin(): bool
    {
        return $this->isOnlyForAdmin;
    }

    public function setBlueprint(BluePrint $bluePrint, $data = null)
    {
        $this->bluePrint = $bluePrint;
        $this->bluePrintData = $data;

        return $this;
    }

    public function text($label, $newValue, $oldValue = null)
    {
        if (isset($this->items[$label])) {
            throw new \Exception('Label already exists');
        }

        if ($this->action == ActionLoggerEnum::UPDATE) {
            if ($oldValue == $newValue) {
                return $this;
            }
        }

        $this->items[$label] = (new \Deep\FormTool\Core\InputTypes\TextType())->setValue($newValue)
            ->getLoggerValue($this->action->value, $oldValue);

        return $this;
    }

    public function date($label, $newValue, $oldValue = null)
    {
        if (isset($this->items[$label])) {
            throw new \Exception('Label already exists');
        }

        if ($this->action == ActionLoggerEnum::UPDATE) {
            if ($oldValue == $newValue) {
                return $this;
            }
        }

        $this->items[$label] = (new \Deep\FormTool\Core\InputTypes\DateType())->setValue($newValue)
            ->getLoggerValue($this->action->value, $oldValue);

        return $this;
    }

    public function time($label, $newValue, $oldValue = null)
    {
        if (isset($this->items[$label])) {
            throw new \Exception('Label already exists');
        }

        if ($this->action == ActionLoggerEnum::UPDATE) {
            if ($oldValue == $newValue) {
                return $this;
            }
        }

        $this->items[$label] = (new \Deep\FormTool\Core\InputTypes\TimeType())->setValue($newValue)
            ->getLoggerValue($this->action->value, $oldValue);

        return $this;
    }

    public function editor($label, $newValue, $oldValue = null)
    {
        if (isset($this->items[$label])) {
            throw new \Exception('Label already exists');
        }

        if ($this->action == ActionLoggerEnum::UPDATE) {
            if ($oldValue == $newValue) {
                return $this;
            }
        }

        $this->items[$label] = (new \Deep\FormTool\Core\InputTypes\EditorType())->setValue($newValue)
            ->getLoggerValue($this->action->value, $oldValue);

        return $this;
    }

    public function image($label, $newValue, $oldValue = null, ?string $disk = null, ?string $visibility = null)
    {
        if (isset($this->items[$label])) {
            throw new \Exception('Label already exists');
        }

        if ($this->action == ActionLoggerEnum::UPDATE) {
            if ($oldValue == $newValue) {
                return $this;
            }
        }

        $input = new \Deep\FormTool\Core\InputTypes\ImageType();
        if ($disk !== null) {
            $input->disk($disk);
        }
        if ($visibility !== null) {
            $input->visibility($visibility);
        }

        $this->items[$label] = $input->setValue($newValue)
            ->getLoggerValue($this->action->value, $oldValue);

        return $this;
    }
}
