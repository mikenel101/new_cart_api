<?php

namespace MikesLumenBase\Utils;

class TaxUtils
{
    const TAX_RULE_EXEMPTION = 0; // 非課税/免税
    const TAX_RULE_INCLUDED_ROUND_DOWN = 1; // 内税:切り捨て
    const TAX_RULE_INCLUDED_ROUND_UP = 2; // 内税:切り上げ
    const TAX_RULE_INCLUDED_ROUND = 3; // 内税:四捨五入
    const TAX_RULE_EXTERNAL_ROUND_DOWN = 4; // 外税:切り捨て
    const TAX_RULE_EXTERNAL_ROUND_UP = 5; // 外税:切り上げ
    const TAX_RULE_EXTERNAL_ROUND = 6; // 外税:四捨五入

    public static function calculateTax($price, $taxRate, $taxRule = self::TAX_RULE_EXEMPTION, $precision = 0)
    {
        switch ($taxRule) {
            // 内税の場合
            case self::TAX_RULE_INCLUDED_ROUND_DOWN:
            case self::TAX_RULE_INCLUDED_ROUND_UP:
            case self::TAX_RULE_INCLUDED_ROUND:
                $taxValue = $price - $price / (1 + $taxRate);
                break;
            // 外税の場合
            case self::TAX_RULE_EXTERNAL_ROUND_DOWN:
            case self::TAX_RULE_EXTERNAL_ROUND_UP:
            case self::TAX_RULE_EXTERNAL_ROUND:
                $taxValue = $price * $taxRate;
                break;
            // 非課税/免税の場合
            default:
                $taxValue = 0;
                break;
        }

        $taxValue = sprintf('%.4f', $taxValue);

        switch ($taxRule) {
            // 切り捨て
            case self::TAX_RULE_INCLUDED_ROUND_DOWN:
            case self::TAX_RULE_EXTERNAL_ROUND_DOWN:
                $taxValue = self::roundDown($taxValue, $precision);
                break;
            // 切り上げ
            case self::TAX_RULE_INCLUDED_ROUND_UP:
            case self::TAX_RULE_EXTERNAL_ROUND_UP:
                $taxValue = self::roundUp($taxValue, $precision);
                break;
            // 四捨五入
            case self::TAX_RULE_INCLUDED_ROUND:
            case self::TAX_RULE_EXTERNAL_ROUND:
                $taxValue = round($taxValue, $precision);
                break;
        }

        return $taxValue;
    }

    protected static function roundDown($tax, $precision)
    {
        if ($precision != 0) {
            $pow = pow(10, $precision);
            $powTax = $tax * $pow;
            $floorTax = floor($powTax);

            return $floorTax / $pow;
        } else {
            return floor($tax);
        }
    }

    protected static function roundUp($tax, $precision)
    {
        if ($precision != 0) {
            $pow = pow(10, $precision);
            $powTax = $tax * $pow;
            $floorTax = ceil($powTax);

            return $floorTax / $pow;
        } else {
            return ceil($tax);
        }
    }

    public static function computePriceWithTax($price, $taxRate, $taxRule, $precision = 0)
    {
        switch ($taxRule) {
            // 外税の場合
            case self::TAX_RULE_EXTERNAL_ROUND_DOWN:
            case self::TAX_RULE_EXTERNAL_ROUND_UP:
            case self::TAX_RULE_EXTERNAL_ROUND:
                $priceWithTax = $price + self::calculateTax($price, $taxRate, $taxRule, $precision);
                break;
            // 内税の場合
            case self::TAX_RULE_INCLUDED_ROUND_DOWN:
            case self::TAX_RULE_INCLUDED_ROUND_UP:
            case self::TAX_RULE_INCLUDED_ROUND:
            // 非課税/免税の場合
            default:
                $priceWithTax = $price;
                break;
        }

        return $priceWithTax;
    }

    public static function computePriceWithoutTax($price, $taxRate, $taxRule, $precision = 0)
    {
        switch ($taxRule) {
            // 内税の場合
            case self::TAX_RULE_INCLUDED_ROUND_DOWN:
            case self::TAX_RULE_INCLUDED_ROUND_UP:
            case self::TAX_RULE_INCLUDED_ROUND:
                $priceWithoutTax = $price - self::calculateTax($price, $taxRate, $taxRule, $precision);
                break;
            // 外税の場合
            case self::TAX_RULE_EXTERNAL_ROUND_DOWN:
            case self::TAX_RULE_EXTERNAL_ROUND_UP:
            case self::TAX_RULE_EXTERNAL_ROUND:
            // 非課税/免税の場合
            default:
                $priceWithoutTax = $price;
                break;
        }

        return $priceWithoutTax;
    }
}
