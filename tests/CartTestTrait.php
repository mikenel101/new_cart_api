<?php

use App\Models\Product;

use MikesLumenBase\Utils\TaxUtils;

trait CartTestTrait
{
    protected $cartSessionId1 = 'mikecart-12345678901234567890123456789000';
    protected $shopId1 = '12345678901234567890123456789001';
    protected $productId1 = '12345678901234567890123456789002';
    protected $productVariantId1 = '12345678901234567890123456789003';
    protected $productId2 = '12345678901234567890123456789004';
    protected $productVariantId2 = '12345678901234567890123456789005';

    protected function getHeaders()
    {
        return [
            'Accept-Language' => 'en',
            'X-Currency' => 'usd',
            'X-ShopId' => $this->shopId1,
            'X-CartSessionId' => $this->cartSessionId1
        ];
    }

    protected function getCartItem1()
    {
        return [
            'product_id' => $this->productId1,
            'product_variant_id' => $this->productVariantId1,
            'sku' => 'cart_item1',
            'quantity' => 10,
            'unit_price' => 1000,
            'product_image' => 'http://example.com/thumbA.jpg',
            'product_name' => 'Product A',
            'stock_mode' => Product::STOCK_MODE_USE,
            'tax_rule' => TaxUtils::TAX_RULE_EXTERNAL_ROUND_UP,
            'tax_rate' => 0.1,
        ];
    }

    protected function getCartItem2()
    {
        return [
            'product_id' => $this->productId2,
            'product_variant_id' => $this->productVariantId2,
            'sku' => 'cart_item2',
            'quantity' => 20,
            'unit_price' => 1000,
            'product_image' => 'http://example.com/thumbA.jpg',
            'product_name' => 'Product B',
            'stock_mode' => Product::STOCK_MODE_USE,
            'tax_rule' => TaxUtils::TAX_RULE_EXTERNAL_ROUND_UP,
            'tax_rate' => 0.1,
        ];
    }
}