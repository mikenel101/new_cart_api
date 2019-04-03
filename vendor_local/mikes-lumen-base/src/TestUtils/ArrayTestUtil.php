<?php

namespace MikesLumenBase\TestUtils;

class ArrayTestUtil
{
    /**
     * $valueが$matcherで指定された値と部分一致するかテストする
     * テストの結果、不一致があれば不一致部分を表す配列を返却する
     * $matcherがネストされた配列の場合、再帰的にテストする
     *
     * Example:
     * <code>
     * ArrayTestUtil::testPartialMatchArray([
     *     'foo' => 1,
     *     'hoge' => [
     *         'fuga' => 3
     *     ],
     * ], [
     *     'foo' => 1,
     *     'bar' => 2,
     *     'hoge' => [
     *         'fuga' => 99,
     *     ]
     * ]);
     * // => ['bar' => 'bar does not exists', 'hoge' => ['fuga' => 'hoge.fuga is not equal. (3 != 99)']]
     *
     * ArrayTestUtil::testPartialMatchArray([
     *     'foo' => 1,
     *     'bar' => 2,
     *     'hoge' => [
     *         'fuga' => 3
     *     ],
     * ], [
     *     'foo' => 1,
     *     'hoge' => []
     * ]);
     * // => []
     * </code>
     *
     * @param $value
     * @param $matcher
     * @return array 不一致点の詳細情報。一致した場合、空配列を返す。
     */
    public static function testPartialMatchArray(array $value, array $matcher)
    {
        return self::testPartialMatchArrayRecursive($value, $matcher, null);
    }

    private static function testPartialMatchArrayRecursive(array $value, array $matcher, $keyContext)
    {
        $errors = [];
        foreach ($matcher as $key => $matcherValue) {
            $internalKeyContext = $keyContext ? "$keyContext.$key" : $key;
            if (array_key_exists($key, $value)) {
                if (is_array($matcherValue)) {
                    if (is_array($value[$key])) {
                        $recursiveUnmatches = self::testPartialMatchArrayRecursive($value[$key], $matcherValue, $internalKeyContext);
                        if ($recursiveUnmatches) {
                            $errors[$key] = $recursiveUnmatches;
                        }
                    } else {
                        $errors[$key] = "$internalKeyContext is not an array.";
                    }
                } else {
                    if ($value[$key] != $matcherValue) {
                        $errors[$key] = "$internalKeyContext is not equal. ({$value[$key]} != $matcherValue)";
                    }
                }
            } else {
                $errors[$key] = "$internalKeyContext does not exist";
            }
        }
        return $errors;
    }
}
