<?php

if (! function_exists('trans')) {
    /**
     * Translate the given message.
     *
     * @param  string  $id
     * @param  array   $parameters
     * @param  string  $domain
     * @param  string  $locale
     * @return string
     */
    function trans($id = null, $parameters = [], $domain = 'messages', $locale = null)
    {
        if (is_null($id)) {
            return app('translator');
        }
        return app('translator')->trans($id, $parameters, $domain, $locale);
    }
}

if (! function_exists('debug')) {
    /**
     * Logging an expression with print format
     *
     * @param  mixed $expression
     * @return void
     */
    function debug($expression)
    {
        \Log::debug(print_r($expression, true));
    }
}
