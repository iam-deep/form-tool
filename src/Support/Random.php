<?php

namespace Deep\FormTool\Support;

use Deep\FormTool\Core\DataModel;

class Random
{
    public static function create(int $length = 32)
    {
        if (\function_exists('random_bytes')) {
            $bytes = \random_bytes(ceil($length / 2));

            return \substr(\bin2hex($bytes), 0, $length);
        } elseif (\function_exists('openssl_random_pseudo_bytes')) {
            $bytes = \openssl_random_pseudo_bytes(ceil($length / 2));

            return \substr(\bin2hex($bytes), 0, $length);
        } else {
            // No cryptographically secure random function available, Let's generate custom

            return self::custom($length);
        }
    }

    public static function custom(int $length = 32, string $characters = null)
    {
        $defaultCharacters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $characters = $characters ?: $defaultCharacters;

        $random = '';
        for ($i = 0; $i < $length; $i++) {
            $random .= $characters[\mt_rand(0, \strlen($characters) - 1)];
        }

        return $random;
    }

    public static function unique(DataModel $model, int $length = 32)
    {
        $token = $model->getTokenCol();

        $random = '';
        do {
            $random = self::create($length);

            $result = $model->getWhere([$token => $random]);
        } while (count($result) > 0);

        return $random;
    }

    /**
     * Create token for all rows of table.
     *
     * This method will check full DB table and create unique token for each row if token is empty or null
     *
     * @param  DataModel  $model  Provide DataModel which you want to modify
     * @param  int  $length  Length of token default is 32
     * @return null
     **/
    public static function createTokenForTable(DataModel $model, int $length = 32)
    {
        $primaryId = $model->getPrimaryId();
        $token = $model->getTokenCol();

        $result = $model->getWhere();
        foreach ($result as $row) {
            if ($row->{$token}) {
                continue;
            }

            $random = self::unique($model, $length);

            $model->updateOne($row->{$primaryId}, [$token => $random]);
        }
    }
}
