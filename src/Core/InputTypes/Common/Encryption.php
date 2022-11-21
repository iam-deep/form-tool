<?php

namespace Biswadeep\FormTool\Core\InputTypes\Common;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

trait Encryption
{
    protected bool $isEncrypted = false;

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

        return $this->doEncrypt($value);
    }

    public function beforeUpdate(object $oldData, object $newData)
    {
        $value = parent::beforeUpdate($oldData, $newData) ?: $this->value;

        // Default need to manage by encryption otherwise non encrypted value will be saved
        if ($value === null) {
            $value = $this->defaultValue;
        }

        return $this->doEncrypt($value);
    }

    public function getValue()
    {
        $this->doDecrypt($this->value);

        return $this->value;
    }

    public function getNiceValue($value)
    {
        $this->doDecrypt($value);

        return $this->value;
    }

    public function isEncrypted()
    {
        return $this->isEncrypted;
    }

    protected function doEncrypt($value)
    {
        if (! $this->isEncrypted || isNullOrEmpty($value)) {
            return $value;
        }

        $this->value = Crypt::encryptString($value);

        return $this->value;
    }

    protected function doDecrypt($value)
    {
        if (! $this->isEncrypted || isNullOrEmpty($value)) {
            return $value;
        }

        try {
            $value = Crypt::decryptString($value);
        } catch (DecryptException $e) {
            $value = $e->getMessage();
        }

        $this->value = $value;

        return $this->value;
    }
}
