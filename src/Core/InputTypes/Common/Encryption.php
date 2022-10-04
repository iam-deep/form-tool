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

        if (! $this->isEncrypted) {
            return $value;
        }

        return Crypt::encryptString($value);
    }

    public function beforeUpdate(object $oldData, object $newData)
    {
        $value = parent::beforeUpdate($oldData, $newData) ?: $this->value;

        if (! $this->isEncrypted) {
            return $value;
        }

        return Crypt::encryptString($value);
    }

    public function getValue()
    {
        $value = $this->value;

        if ($this->isEncrypted) {
            try {
                $value = Crypt::decryptString($value);
            } catch (DecryptException $e) {
                $value = $e->getMessage();
            }
        }

        return $value;
    }

    public function getTableValue()
    {
        $value = $this->value;

        if ($this->isEncrypted) {
            try {
                $value = Crypt::decryptString($value);
            } catch (DecryptException $e) {
                $value = $e->getMessage();
            }
        }

        $this->value = $value;

        return parent::getTableValue();
    }
}
