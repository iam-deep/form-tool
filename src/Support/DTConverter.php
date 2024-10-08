<?php

namespace Deep\FormTool\Support;

use DateTime;
use DateTimeZone;

class DTConverter
{
    public static $timezone = 'UTC';
    public static $appTimezone = 'UTC';

    public static $dbFormatDateTime = 'Y-m-d H:i:s';
    public static $dbFormatDate = 'Y-m-d';
    public static $dbFormatTime = 'H:i:s';

    public static $niceFormatDateTime = 'd-m-Y h:i A';
    public static $niceFormatDate = 'd-m-Y';
    public static $niceFormatTime = 'h:i A';

    public static function init()
    {
        self::$appTimezone = \config('app.timezone', 'UTC');
        self::$timezone = \trim(Settings::get('timezone', self::$appTimezone));

        self::$niceFormatDate = \trim(Settings::get(
            'dateFormat',
            \config('form-tool.formatDate') ?: self::$niceFormatDate
        ));

        self::$niceFormatTime = \trim(Settings::get(
            'timeFormat',
            \config('form-tool.formatTime') ?: self::$niceFormatTime
        ));

        self::$niceFormatDateTime = self::$niceFormatDate.' '.self::$niceFormatTime;
    }

    public static function niceDate(?string $date, bool $isConvertToLocal = false, ?string $format = null): ?string
    {
        if (! $date) {
            return null;
        }

        try {
            // create a $dt object with the UTC timezone
            $dt = new DateTime($date, new DateTimeZone(self::$appTimezone));

            if ($isConvertToLocal) {
                // change the timezone of the object without changing its time
                $dt->setTimezone(new DateTimeZone(self::$timezone));
            }

            // format the datetime
            return $dt->format($format ?: self::$niceFormatDate);
        } catch(\Exception $e) {
            return null;
        }
    }

    public static function niceTime(?string $time, bool $isConvertToLocal = false, ?string $format = null): ?string
    {
        if (! $time) {
            return null;
        }

        try {
            // create a $dt object with the UTC timezone
            $dt = new DateTime($time, new DateTimeZone(self::$appTimezone));

            if ($isConvertToLocal) {
                // change the timezone of the object without changing its time
                $dt->setTimezone(new DateTimeZone(self::$timezone));
            }

            // format the datetime
            return $dt->format($format ?: self::$niceFormatTime);
        } catch(\Exception $e) {
            return null;
        }
    }

    public static function niceDateTime(
        ?string $dateTime,
        bool $isConvertToLocal = false,
        ?string $format = null
    ): ?string {
        if (! $dateTime) {
            return null;
        }

        try {
            // create a $dt object with the UTC timezone
            $dt = new DateTime($dateTime, new DateTimeZone(self::$appTimezone));

            if ($isConvertToLocal) {
                // change the timezone of the object without changing its time
                $dt->setTimezone(new DateTimeZone(self::$timezone));
            }

            // format the datetime
            return $dt->format($format ?: self::$niceFormatDateTime);
        } catch(\Exception $e) {
            return null;
        }
    }

    public static function dbDate(?string $date, bool $isConvertToUTC = false): ?string
    {
        if (! $date) {
            return null;
        }

        try {
            // create a $dt object with the local timezone
            $dt = new DateTime($date, new DateTimeZone(self::$timezone));

            if ($isConvertToUTC) {
                // convert to UTC or project's timezone
                $dt->setTimezone(new DateTimeZone(self::$appTimezone));
            }

            // format the datetime
            return $dt->format(self::$dbFormatDate);
        } catch (\Exception $e) {
            // Hiding date parsing errors from users
        }

        return null;
    }

    public static function dbTime(?string $time, bool $isConvertToUTC = false): ?string
    {
        if (! $time) {
            return null;
        }

        try {
            // create a $dt object with the local timezone
            $dt = new DateTime($time, new DateTimeZone(self::$timezone));

            if ($isConvertToUTC) {
                // convert to UTC or project's timezone
                $dt->setTimezone(new DateTimeZone(self::$appTimezone));
            }

            // format the datetime
            return $dt->format(self::$dbFormatTime);
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function dbDateTime(?string $dateTime, bool $isConvertToUTC = false): ?string
    {
        if (! $dateTime) {
            return null;
        }

        try {
            // create a $dt object with the local timezone
            $dt = new DateTime($dateTime, new DateTimeZone(self::$timezone));

            if ($isConvertToUTC) {
                // convert to UTC or project's timezone
                $dt->setTimezone(new DateTimeZone(self::$appTimezone));
            }

            // format the datetime
            return $dt->format(self::$dbFormatDateTime);
        } catch (\Exception $e) {
            // Hiding date parsing errors from users
        }

        return null;
    }

    public static function toNice(?string $dateTime, string $format, bool $isConvertToLocal = false): ?string
    {
        if (! $dateTime) {
            return null;
        }

        try {
            // create a $dt object with the UTC timezone
            $dt = new DateTime($dateTime, new DateTimeZone(self::$appTimezone));

            if ($isConvertToLocal) {
                // change the timezone of the object without changing its time
                $dt->setTimezone(new DateTimeZone(self::$timezone));
            }

            // format the datetime
            return $dt->format($format);
        } catch(\Exception $e) {
            return null;
        }
    }

    public static function toDb(?string $dateTime, string $format, bool $isConvertToUTC = false): ?string
    {
        if (! $dateTime) {
            return null;
        }

        try {
            // create a $dt object with the local timezone
            $dt = new DateTime($dateTime, new DateTimeZone(self::$timezone));

            if ($isConvertToUTC) {
                // convert to UTC or project's timezone
                $dt->setTimezone(new DateTimeZone(self::$appTimezone));
            }

            // format the datetime
            return $dt->format($format);
        } catch (\Exception $e) {
            // Hiding date parsing errors from users
        }

        return null;
    }

    public static function toLocal(?string $dateTime, string $format, bool $isConvertToLocal = true): ?string
    {
        if (! $dateTime) {
            return null;
        }

        try {
            // create a $dt object with UTC or project's timezone
            $dt = new DateTime($dateTime, new DateTimeZone(self::$appTimezone));

            if ($isConvertToLocal) {
                // convert to local timezone
                $dt->setTimezone(new DateTimeZone(self::$timezone));
            }

            // format the datetime
            return $dt->format($format);
        } catch (\Exception $e) {
            // Hiding date parsing errors from users
        }

        return null;
    }

    public static function getTimezones()
    {
        $continents = [
            'Africa' => DateTimeZone::AFRICA,
            'America' => DateTimeZone::AMERICA,
            'Antarctica' => DateTimeZone::ANTARCTICA,
            'Arctic' => DateTimeZone::ARCTIC,
            'Asia' => DateTimeZone::ASIA,
            'Atlantic' => DateTimeZone::ATLANTIC,
            'Australia' => DateTimeZone::AUSTRALIA,
            'Europe' => DateTimeZone::EUROPE,
            'Indian' => DateTimeZone::INDIAN,
            'Pacific' => DateTimeZone::PACIFIC,
        ];

        $tzlist = ['UTC' => '(GMT/UTC '.self::getOffset('UTC').') &#160; - &#160; GMT/UTC'];
        $timezones = [];
        foreach ($continents as $mask) {
            $timezones = DateTimeZone::listIdentifiers($mask);

            foreach ($timezones as $timezone) {
                $tzlist[$timezone] = '(GMT/UTC '.self::getOffset($timezone).') &#160; - &#160; '.$timezone;
            }
        }

        return $tzlist;
    }

    protected static function getOffset($timezone)
    {
        $time = new DateTime('', new DateTimeZone($timezone));

        return $time->format('P');
    }
}
