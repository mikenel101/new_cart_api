<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Custom Validation
    |--------------------------------------------------------------------------
    */

    'alphanumeric'  => ':attribute は英数字でなければいけません。',
    'bankaccount'   => ':attribute が有効な文字列ではありません。',
    'cant_start_with' => ':attribute が :prefix で始まる文字列は指定できません。',
    'exists_or_zero'=> '選択された:attributeは、有効ではありません。',
    'htmltagcheck'  => ':attribute はHTMLタグにしてください。',
    'katakana'      => ':attribute はカタカナで入力してください。',
    'max_length'    => ':attribute は :value文字以下にしてください',
    'min_length'    => ':attribute は :value文字以上にしてください',
    'move' => ':attribute は移動できません。',
    'noblank'    => ':attribute は空白・タブ・改行を含んではいけません。',
    'numericarray'  => ':attribute は数値のみの配列にしてください。',
    'password'      => ':attribute が有効な文字列ではありません。',
    'stringarray'  => ':attribute は文字列のみの配列にしてください。',
    'uuid'  => ':attribute はUUIDを指定してください。',
    'uuidarray'  => ':attribute はUUIDのみの配列にしてください。',
    'zerostart'     => ':attribute は0から始めてください。',
    'unique'     => '入力された:attribute は重複禁止の値です。',
    'header'     => 'リクエストヘッダの値が不正です。',
];
