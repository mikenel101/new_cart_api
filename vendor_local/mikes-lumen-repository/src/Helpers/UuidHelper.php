<?php

namespace MikesLumenRepository\Helpers;

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

class UuidHelper
{
    /** UUIDのバイト数 */
    const UUID_BINARY_SIZE = 16;

    /** 16進文字列の長さ */
    const UUID_HEX_STRING_LENGTH = self::UUID_BINARY_SIZE * 2;

    public static function toUuidExpression($uuid)
    {
        if ($uuid instanceof Expression) {
            return $uuid;
        }

        $hex = self::toHex($uuid);
        if ($hex) {
            return DB::raw("UNHEX('{$hex}')");
        } else {
            return DB::raw("NULL");
        }
    }

    /**
     * 引数がUUIDを16進表記で表現した文字列か否かを判定する。
     * @return bool
     */
    public static function isUuidString($value)
    {
        return is_string($value) && strlen($value) == self::UUID_HEX_STRING_LENGTH && ctype_xdigit($value);
    }

    /**
     * バイナリ形式のUUIDを16進表記に変換する。
     * 元々16進表記だった場合はそのまま
     */
    public static function toHex($value)
    {
        if (!is_string($value)) {
            return '';
        } elseif (self::isUuidString($value)) {
            return $value;
        } else if (strlen($value) == self::UUID_BINARY_SIZE) {
            return bin2hex($value);
        } else {
            return '';
        }
    }

    /**
     * 16進表記のUUIDをバイナリ形式に変換する。
     * 元々バイナリ形式だった場合はそのまま
     */
    public static function toBin($value)
    {
        if (!is_string($value)) {
            return '';
        } elseif (self::isUuidString($value)) {
            return hex2bin($value);
        } else if (strlen($value) == self::UUID_BINARY_SIZE) {
            return $value;
        } else {
            return '';
        }
    }

    /**
     * $valueがuuidであるか判定。
     * nullは含まない。
     * 通常の文字列でもuuidと判定される可能性があるので注意。
     */
    public static function isUuidValue($value)
    {
        return is_string($value) && (strlen($value) == self::UUID_BINARY_SIZE);
    }
}
