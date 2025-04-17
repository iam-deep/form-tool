<?php

namespace Deep\FormTool\Dtos;

use Closure;

class MultipleTableDto
{
    public bool $isOrderable = false;
    public ?string $orderableColumn = null;

    /**
     * @param  class-string<\Deep\FormTool\Models\BaseModel>  $className
     */
    public function __construct(
        public readonly string $modelType = 'table',
        public readonly ?string $tableName = null,
        public readonly ?string $className = null,
        public readonly ?string $primaryCol = null,
        public readonly ?string $foreignCol = null,
        public readonly ?string $orderBy = null,
        public readonly ?Closure $where = null,
    ) {
        if ($modelType == 'class') {
            if (! class_exists($className)) {
                throw new \InvalidArgumentException('Class not found. Class: '.$className);
            }

            if (! $className::$tableName || ! $className::$primaryId || ! $className::$foreignKey) {
                throw new \InvalidArgumentException('$tableName, $primaryId or $foreignKey property not defined at '.$className);
            }
        }
    }
}
