<?php

namespace Deep\FormTool\Dtos;

use Deep\FormTool\Enums\ActionLoggerEnum;

class ActionLoggerDto
{
    public function __construct(
        public readonly ActionLoggerEnum $action,
        public readonly string $moduleTitle,
        public readonly ?array $data = null,
        public readonly ?string $description = null,
        public readonly ?string $route = null,
        public readonly ?string $nameOfTheData = null,
        public readonly ?string $id = null,
        public readonly ?string $token = null,
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
    ) {
    }
}
