<?php

namespace MikesLumenRepository\Providers;

use Illuminate\Support\ServiceProvider;
use MikesLumenRepository\Helpers\UuidHelper;

class ValidatorServiceProvider extends ServiceProvider
{

    /**
     * Translate multi language for API message
     *
     * @param string $key
     * @param array $params
     * @param string $domain
     * @param string $locale
     *
     * @return string
     */
    private function trans($key, $params = [], $domain = 'messages', $locale = null)
    {
        return app('translator')->trans($key, $params, $domain, $locale);
    }

    public function boot()
    {
        // TODO

        $this->app['validator']->extend('noblank', function ($attribute, $value, $parameters) {
            if (strlen($value) != 0 && preg_match("/[　 \t\r\n]+/u", $value)) {
                return false;
            }
            return true;
        });

        $this->app['validator']->extend('numericarray', function ($attribute, $value, $parameters) {
            foreach ($value as $v) {
                if (!is_int($v)) {
                    return false;
                }
            }
            return true;
        });

        $this->app['validator']->extend('stringarray', function ($attribute, $value, $parameters) {
            foreach ($value as $v) {
                if (!is_string($v)) {
                    return false;
                }
            }
            return true;
        });

        $this->app['validator']->extend('uuid', function ($attribute, $value, $parameters) {
            if (!UuidHelper::isUuidString($value)) {
                return false;
            }
            return true;
        });

        $this->app['validator']->extend('uuidarray', function ($attribute, $value, $parameters) {
            foreach ($value as $v) {
                if (!UuidHelper::isUuidString($v)) {
                    return false;
                }
            }
            return true;
        });

        $this->app['validator']->extend('katakana', function ($attribute, $value, $parameters) {
            if (app('translator')->getLocale() != 'ja') {
                return true;
            }
            if (strlen($value) != 0 && !preg_match("/^[ァ-ヶｦ-ﾟー]+$/u", $value)) {
                return false;
            }
            return true;
        });

        $this->app['validator']->extend('password', function ($attribute, $value, $parameters) {
            if (strlen($value) != 0 && !preg_match("/^[a-zA-Z0-9-_%!\|&#\+\?@\(\)\[\]\{\}*.,]+$/u", $value)) {
                return false;
            }
            return true;
        });

        $this->app['validator']->extend('startwith', function ($attribute, $value, $parameters) {
            foreach ($parameters as $parameter) {
                if (strpos($value, $parameter) !== false) {
                    return false;
                }
            }
            return true;
        });

        $this->app['validator']->extend('alphanumeric', function ($attribute, $value, $parameters) {
            if (strlen($value) != 0 && !preg_match("/^[a-zA-Z0-9]+$/", $value)) {
                return false;
            }
            return true;
        });

        $this->app['validator']->replacer('noblank', function ($message, $attribute, $rule, $parameters) {
            return $this->trans('mikelumenrepository::validation.noblank', ["attribute" => $attribute]);
        });

        $this->app['validator']->replacer('numericarray', function ($message, $attribute, $rule, $parameters) {
            return $this->trans('mikelumenrepository::validation.numericarray', ["attribute" => $attribute]);
        });

        $this->app['validator']->replacer('stringarray', function ($message, $attribute, $rule, $parameters) {
            return $this->trans('mikelumenrepository::validation.stringarray', ["attribute" => $attribute]);
        });

        $this->app['validator']->replacer('uuid', function ($message, $attribute, $rule, $parameters) {
            return $this->trans('mikelumenrepository::validation.uuid', ["attribute" => $attribute]);
        });

        $this->app['validator']->replacer('uuidarray', function ($message, $attribute, $rule, $parameters) {
            return $this->trans('mikelumenrepository::validation.uuidarray', ["attribute" => $attribute]);
        });

        $this->app['validator']->replacer('katakana', function ($message, $attribute, $rule, $parameters) {
            return $this->trans('mikelumenrepository::validation.katakana', ["attribute" => $attribute]);
        });

        $this->app['validator']->replacer('password', function ($message, $attribute, $rule, $parameters) {
            return $this->trans('mikelumenrepository::validation.password', ["attribute" => $attribute]);
        });

        $this->app['validator']->replacer('startwith', function ($message, $attribute, $rule, $parameters) {
            return $this->trans('mikelumenrepository::validation.cant_start_with', ["attribute" => $attribute, "prefix" => implode(',', $parameters)]);
        });

        $this->app['validator']->replacer('alphanumeric', function ($message, $attribute, $rule, $parameters) {
            return $this->trans('mikelumenrepository::validation.alphanumeric', ["attribute" => $attribute]);
        });
    }

    public function register()
    {
        //
    }
}
