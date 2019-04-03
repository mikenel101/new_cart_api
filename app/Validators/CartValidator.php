<?php

namespace App\Validators;

use App\Models\Product;
use MikesLumenRepository\Validators\LumenValidator;

class CartValidator extends LumenValidator
{
    public const ADD_CART_ITEM_RULE = 'add_cart_item_rule';
    public const REMOVE_CART_ITEM_RULE = 'remove_cart_item_rule';

    protected $rules = [
        self::ADD_CART_ITEM_RULE => [
            'product_id' => 'required|uuid',
            'product_variant_id' => 'required|uuid',
            'sku' => 'required|noblank',
            'quantity' => 'required|numeric',
            'unit_price' => 'required|numeric|min:1',
            'stock_mode' => 'required|numeric|in:' . Product::STOCK_MODE_USE . ',' . Product::SHIPPING_MODE_UNUSED
        ],
        self::REMOVE_CART_ITEM_RULE => [
            'product_id' => 'uuid|nullable',
            'product_variant_id' => 'required|uuid',
        ],
    ];
}
