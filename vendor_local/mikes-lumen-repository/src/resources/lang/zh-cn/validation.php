<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Custom Validation
    |--------------------------------------------------------------------------
    */

    'alphanumeric'  => ':attribute 必须是英文、数字。',
    'bankaccount'   => ':attribute 不是有效的字符串。',
    'cant_start_with' => ':attribute 不能指定为以:prefix开头的字符串。',
    'exists_or_zero'=> '选择的:attribute是无效的。',
    'htmltagcheck'  => ':attribute 请使用HTML标签。',
    'katakana'      => ':attribute 请以拼音输入。',
    'max_length'    => ':attribute 需要 :value位以下',
    'min_length'    => ':attribute 需要 :value位以上',
    'move' => ':attribute 不能移动',
    'noblank'    => ':attribute 不能含有空格、tab符、换行符。',
    'numericarray'  => ':attribute 只能是数字数组。',
    'password'      => ':attribute 不是有效的字符串。',
    'stringarray'  => ':attribute 只能是字符串数组。',
    'uuid'  => ':attribute 请指定UUID',
    'uuidarray'  => ':attribute 只能是UUID的数组。',
    'zerostart'     => ':attribute 请以0开始。',
    'unique'     => ':attribute 是重复的禁止值',
    'header'     => '请求头的值是无效的',
];
