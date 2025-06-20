<?php

use Carbon\Carbon;
use Deep\FormTool\Core\Doc;
use Deep\FormTool\Core\Guard;
use Deep\FormTool\Exceptions\FormToolException;
use Illuminate\Support\Arr;

if (! function_exists('addCssLink')) {
    function addCssLink($link)
    {
        Doc::addCssLink($link);
    }
}

if (! function_exists('addJsLink')) {
    function addJsLink($link)
    {
        Doc::addJsLink($link);
    }
}

if (! function_exists('addJs')) {
    function addJs($script)
    {
        Doc::addJs($script);
    }
}

if (! function_exists('addCss')) {
    function addCss($css)
    {
        Doc::addCss($css);
    }
}

if (! function_exists('getCssLinks')) {
    function getCssLinks()
    {
        return Doc::getCssLinks();
    }
}

if (! function_exists('getJsLinks')) {
    function getJsLinks()
    {
        return Doc::getJsLinks();
    }
}

if (! function_exists('getCss')) {
    function getCss()
    {
        return Doc::getCss();
    }
}

if (! function_exists('getJs')) {
    function getJs()
    {
        return Doc::getJs();
    }
}

if (! function_exists('getJsGroup')) {
    function getJsGroup($group)
    {
        return Doc::getJsGroup($group);
    }
}

// TODO: Need to change this function name as per Package name
if (! function_exists('getFormCss')) {
    function getFormCss()
    {
        return Doc::getCssLinks().Doc::getCss();
    }
}

// TODO: Need to change this function name as per Package name
if (! function_exists('getFormJs')) {
    function getFormJs()
    {
        return Doc::getJsLinks().Doc::getJs();
    }
}

if (! function_exists('decodeHTML')) {
    function decodeHTML($data)
    {
        return (new Deep\FormTool\Core\InputTypes\EditorType())->decodeHTML($data);
    }
}

if (! function_exists('encryptText')) {
    function encryptText($value)
    {
        return (new Deep\FormTool\Core\InputTypes\TextType())->encrypt()->doEncrypt($value);
    }
}

if (! function_exists('decryptText')) {
    function decryptText($value)
    {
        return (new Deep\FormTool\Core\InputTypes\TextType())->encrypt()->doDecrypt($value);
    }
}

if (! function_exists('imageThumb')) {
    function imageThumb($value)
    {
        return (new Deep\FormTool\Core\InputTypes\ImageType())->getNiceValue($value);
    }
}

if (! function_exists('imageResize')) {
    function imageResize($imagePath, $width = null, $height = null)
    {
        return \Deep\FormTool\Support\ImageCache::resize($imagePath, $width, $height);
    }
}

if (! function_exists('imageFit')) {
    function imageFit($imagePath, $width = null, $height = null)
    {
        return \Deep\FormTool\Support\ImageCache::fit($imagePath, $width, $height);
    }
}

if (! function_exists('isSuccess')) {
    function isSuccess($response)
    {
        if (is_array($response)) {
            return $response['status'] ?? false;
        }

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $data = $response->getData();

            return isset($data->status) && $data->status === true;
        }

        return $response instanceof \Illuminate\Http\RedirectResponse && session()->has('success');
    }
}

if (! function_exists('getSideMenu')) {
    function getSideMenu()
    {
        return \Deep\FormTool\Support\Menu::generate();
    }
}

if (! function_exists('guard')) {
    function guard()
    {
        return Guard::class;
    }
}

if (! function_exists('removeSlash')) {
    function removeSlash($path)
    {
        return \str_replace(['/', '\\'], '', $path);
    }
}

if (! function_exists('isNullOrEmpty')) {
    function isNullOrEmpty($value)
    {
        return $value === null || trim($value) === '';
    }
}

if (! function_exists('niceDateTime')) {
    function niceDateTime(?string $datetime, bool $isConvertToLocal = true, ?string $format = null)
    {
        return \Deep\FormTool\Support\DTConverter::niceDateTime($datetime, $isConvertToLocal, $format);
    }
}

if (! function_exists('niceDate')) {
    function niceDate(?string $date, bool $isConvertToLocal = true, ?string $format = null)
    {
        return \Deep\FormTool\Support\DTConverter::niceDate($date, $isConvertToLocal, $format);
    }
}

if (! function_exists('niceTime')) {
    function niceTime(?string $time, bool $isConvertToLocal = true, ?string $format = null)
    {
        return \Deep\FormTool\Support\DTConverter::niceTime($time, $isConvertToLocal, $format);
    }
}

if (! function_exists('toNice')) {
    function toNice(?string $datetime, string $format, bool $isConvertToLocal = false)
    {
        return \Deep\FormTool\Support\DTConverter::toNice($datetime, $format, $isConvertToLocal);
    }
}

if (! function_exists('toLocal')) {
    function toLocal(?string $datetime, string $format, bool $isConvertToLocal = true)
    {
        return \Deep\FormTool\Support\DTConverter::toLocal($datetime, $format, $isConvertToLocal);
    }
}

if (! function_exists('dbDate')) {
    function dbDate(?string $date, bool $isConvertToUTC = false)
    {
        return \Deep\FormTool\Support\DTConverter::dbDate($date, $isConvertToUTC);
    }
}

if (! function_exists('dbDateTime')) {
    function dbDateTime(?string $datetime, bool $isConvertToUTC = false)
    {
        return \Deep\FormTool\Support\DTConverter::dbDateTime($datetime, $isConvertToUTC);
    }
}

if (! function_exists('getDependencies')) {
    function getDependencies($plugins, ...$configs)
    {
        $plugins = Arr::wrap($plugins);

        foreach ($plugins as $plugin) {
            switch ($plugin) {
                case 'datetime':
                    (new Deep\FormTool\Core\InputTypes\BaseDateTimeType())->setDependencies();
                    break;

                case 'chosen':
                    (new Deep\FormTool\Core\InputTypes\SelectType())->setDependencies();
                    break;

                case 'ckeditor':
                    $editor = (new Deep\FormTool\Core\InputTypes\EditorType())->plugin('ckeditor');
                    $editor->setDependencies('ckeditor');
                    if ($configs) {
                        $editor->setJs(...$configs);
                    }
                    break;

                default:
                    throw new FormToolException(sprintf('Plugin not found: %s', $plugin));
                    break;
            }
        }
    }
}

if (! function_exists('dateHumanDiff')) {
    function dateHumanDiff($datetime)
    {
        $dt = Carbon::parse($datetime);

        return $dt->diffForHumans();
    }
}

if (! function_exists('createUrl')) {
    function createUrl($route, $queryParams = null)
    {
        $callbackUrl = config('form-tool.callbackUrl');
        if ($callbackUrl) {
            return $callbackUrl($route, $queryParams);
        }

        if ($route && $route[0] == '/') {
            $route = substr($route, 1);
        }

        if ($queryParams) {
            $queryParams = is_array($queryParams) ? http_build_query($queryParams) : $queryParams;

            if (strpos($route, '?') !== false) {
                $route .= '&'.$queryParams;
            } else {
                $route .= '?'.$queryParams;
            }
        }

        $adminDir = config('form-tool.adminURL');
        if ($adminDir) {
            return url($adminDir.'/'.$route);
        }

        return url($route);
    }
}
