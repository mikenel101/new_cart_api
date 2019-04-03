<?php

namespace MikesLumenBase\Utils;

class CustomFieldUtils
{
    // 必須設定
    const REQUIRED_REQUIRED = 1;    // 必須
    const REQUIRED_ANY = 0;         // 任意

    // 重複制限
    const IS_UNIQUE_BAN = 1;        // 禁止
    const IS_UNIQUE_ALLOWANCE = 0;  // 許容

    const IS_TRANSLATABLE = 1;        // 翻訳
    const IS_NOT_TRANSLATABLE = 0;        // 翻訳なし

    // 入力欄タイプ
    const FIELD_TYPE_TEXT = 1;             // テキスト
    const FIELD_TYPE_SELECT_BOX = 2;       // セレクトボックス
    const FIELD_TYPE_CHECK_BOX = 3;        // チェックボックス
    const FIELD_TYPE_RADIO_BUTTON = 4;     // ラジオボタン
    const FIELD_TYPE_TEXT_AREA = 5;        // テキストエリア
    const FIELD_TYPE_NUMBER = 6;           // 数値
    const FIELD_TYPE_EMAIL_ADDRESS = 7;    // メールアドレス
    const FIELD_TYPE_DATETIME = 8;         // 日時
    const FIELD_TYPE_MEDIA = 9;            // 画像

    // 内容制限
    const STRING_VALIDATION_TYPE_NONE = 1;              // 制限なし
    const STRING_VALIDATION_TYPE_ALPHA_NUMERIC = 2;     // 半角英数
    const STRING_VALIDATION_TYPE_ALPHA = 3;             // 半角英字
    const STRING_VALIDATION_TYPE_NUMERIC = 4;           // 半角数字
    const STRING_VALIDATION_TYPE_ALPHA_DASH = 5;        // 半角英数記号
    const STRING_VALIDATION_TYPE_DOUBLE_BYTE_CHAR = 6;  // 全角

    // 数値制限
    const NUMBER_VALIDATION_TYPE_INTEGER = 1;   // 整数
    const NUMBER_VALIDATION_TYPE_DECIMAL = 2;   // 小数
}
