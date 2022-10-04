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

        return $this->doEncrypt($value);
    }

    public function beforeUpdate(object $oldData, object $newData)
    {
        $value = parent::beforeUpdate($oldData, $newData) ?: $this->value;

        return $this->doEncrypt($value);
    }

    public function getValue()
    {
        $this->doDecrypt();

        return parent::getValue();
    }

    public function getTableValue()
    {
        $this->doDecrypt();

        return parent::getTableValue();
    }

    protected function doEncrypt($value)
    {
        if (! $this->isEncrypted || ! $value) {
            return $value;
        }

        $this->value = $value;

        return Crypt::encryptString($value);
    }

    protected function doDecrypt()
    {
        if ($this->isEncrypted && $this->value) {
            try {
                $this->value = Crypt::decryptString($this->value);
            } catch (DecryptException $e) {
                $this->value = $e->getMessage();
            }
        }

        return $this->value;
    }
}
