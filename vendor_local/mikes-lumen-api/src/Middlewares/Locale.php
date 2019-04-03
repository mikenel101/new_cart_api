<?php

namespace MikesLumenApi\Middlewares;

use Closure;

class Locale
{
    /**
     * Run the request filter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $queryLang = $request->input('lang');
        if (!is_null($queryLang)) {
            $headerLocale = $queryLang;
        } else {
            $headerLocale = $request->header('accept-language');
        }

        $availableLanguages = env('AVAILABLE_LANGUAGES') ?? 'en,ja';
        $supportedLanguages = explode(',', $availableLanguages);

        $locale = (!is_null($headerLocale) && in_array($headerLocale, $supportedLanguages))
            ? $headerLocale
            : env('APP_LOCALE', 'en');

        app('translator')->setLocale($locale);

        return $next($request);
    }
}
