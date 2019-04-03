<?php

namespace MikesLumenBase\Utils;

class CurrencyUtil
{
    public const PRECISIONS = [
        'JPY' => 0,
        'USD' => 2,
    ];

    public static function format($price, $currency, $locale = null)
    {
        $currency = strtoupper($currency);
        $precision = self::PRECISIONS[$currency];
        return app('translator')->trans("mikelumenbase::currency.$currency", ['price' => number_format($price, $precision)], "message", $locale);
    }
}
