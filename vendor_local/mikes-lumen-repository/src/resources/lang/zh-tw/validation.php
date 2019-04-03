<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Custom Validation
    |--------------------------------------------------------------------------
    */

    'alphanumeric'  => ':attribute 必須是英文、數字。',
    'bankaccount'   => ':attribute 不是有效的字元串。',
    'cant_start_with' => ':attribute 不能指定為以:prefix開頭的字元串。',
    'exists_or_zero'=> '選擇的:attribute是無效的。',
    'htmltagcheck'  => ':attribute 請使用HTML標籤。',
    'katakana'      => ':attribute 請以拼音輸入。',
    'max_length'    => ':attribute 需要 :value位以下',
    'min_length'    => ':attribute 需要 :value位以上',
    'move' => ':attribute 不能移動',
    'noblank'    => ':attribute 不能含有空格、tab符、換行符。',
    'numericarray'  => ':attribute 只能是數字數組。',
    'password'      => ':attribute 不是有效的字元串。',
    'stringarray'  => ':attribute 只能是字元串數組。',
    'uuid'  => ':attribute 請指定UUID',
    'uuidarray'  => ':attribute 是只有UUID的數組。',
    'zerostart'     => ':attribute 請以0開始。',
    'unique'     => ':attribute 是重複的禁止值',
    'header'     => '請求頭的值是無效的',
];
