<?php

namespace MikesLumenBase\Utils;

class Hasher
{
    const AUTH_MAGIC = "31eafcbd7a81d7b401a7fdc12bba047c02d1fae6";
    const DEFAULT_PASSWORD_LENGTH = 8;

    /**
     * Hash the given value.
     *
     * @param  string $value
     * @param  array  $options
     *
     * @return string
     */
    public static function make($value, array $options = [])
    {
        return sha1($value . ':' . Hasher::AUTH_MAGIC);
    }

    /**
     * Check the given plain value against a hash.
     *
     * @param  string $value
     * @param  string $hashedValue
     * @param  array  $options
     *
     * @return bool
     */
    public static function check($value, $hashedValue, $options = [])
    {
        return (Hasher::make($value) == $hashedValue);
    }

    public static function needsRehash($hashedValue, $options = [])
    {
        return $hashedValue;
    }
}
