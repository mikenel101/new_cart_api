<?php

namespace MikesLumenBase\TestUtils;

trait ArrayAssertTrait
{
    /**
     * ArrayTestUtil::testPartialMatchArrayを利用して配列の値が期待通りか確認する。
     * @see ArrayTestUtil::testPartialMatchArray()
     *
     * @param array $expected
     * @param array $actual
     */
    public function assertArrayPartialMatch(array $expected, array $actual)
    {
        $result = ArrayTestUtil::testPartialMatchArray($actual, $expected);
        if ($result) {
            $errors = array_flatten($result);
            $this->fail("Unexpected array value.\n  " . implode("\n  ", $errors));
        }
    }
}
