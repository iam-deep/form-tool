<?php

namespace Biswadeep\FormTool\Core\InputTypes\Common;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

trait Encryption
{
    protected bool $isEncrypted = false;

    private string $valueEncrypted = '';
    private string $valueDecrypted = '';

    public function encrypt()
    {
        $this->isEncrypted = true;

        return $this;
    }

    public function beforeStore(object $newData)
    {
        $value = parent::beforeStore($newData) ?: $this->value;

        // Default need to manage by encryption otherwise non encrypted value will be saved
        if ($value === null) {
            $value = $this->defaultValue;
        }

        $this->value = $this->doEncrypt($value);

        return $this->value;
    }

    public function beforeUpdate(object $oldData, object $newData)
    {
        $value = parent::beforeUpdate($oldData, $newData) ?: $this->value;

        // Default need to manage by encryption otherwise non encrypted value will be saved
        if ($value === null) {
            $value = $this->defaultValue;
        }

        $this->value = $this->doEncrypt($value);

        return $this->value;
    }

    public function getValue()
    {
        $this->value = $this->doDecrypt($this->value);

        return $this->value;
    }

    public function getNiceValue($value)
    {
        $value = $this->doDecrypt($value);

        return $value;
    }

    public function getLoggerValue(string $action, $oldValue = null)
    {
        if (! $this->isEncrypted) {
            return parent::getLoggerValue($action, $oldValue);
        }

        if ($action == 'update') {
            // oldValue will be database value and will be encrypted string
            $oldValueDecrypted = $this->doDecrypt($oldValue);
            $newValueDecrypted = $this->doDecrypt($this->value);
            if ($newValueDecrypted != $oldValueDecrypted) {
                return [
                    'type' => 'encrypted',
                    'data' => [$oldValue, $this->value],
                ];
            }

            return '';
        }

        return $this->value ? ['type' => 'encrypted', 'data' => $this->value] : '';
    }

    public function isEncrypted()
    {
        return $this->isEncrypted;
    }

    public function doEncrypt($value)
    {
        if (! $this->isEncrypted || isNullOrEmpty($value)) {
            return $value;
        }

        $this->valueDecrypted = $value;

        $value = Crypt::encryptString($value);

        $this->valueEncrypted = $value;

        return $value;
    }

    public function doDecrypt($value)
    {
        if (! $this->isEncrypted || isNullOrEmpty($value)) {
            return $value;
        }

        $this->valueEncrypted = $value;

        try {
            $value = Crypt::decryptString($value);
        } catch (DecryptException $e) {
            $value = $e->getMessage();
        }

        $this->valueDecrypted = $value;

        return $value;
    }
}
