<?php

namespace App\Validators;

use MikesLumenRepository\Validators\LumenValidator;

class CheckoutValidator extends LumenValidator
{
    public const CART_CHECK_OUT_RULE = 'cart_check_out_rule';

    protected $rules = [
        self::CART_CHECK_OUT_RULE => [
            'email' => 'required|noblank|email',
            'shop_id' => 'required|uuid|noblank',
            'currency' => 'required|string|min:3|max:3',

            'order_items' => 'required|array',
            'order_items.0' => 'required|array',
            'order_items.*.product_id' => 'required|uuid',
            'order_items.*.product_variant_id' => 'required|uuid',
            'order_items.*.product_name' => 'required|string',
            'order_items.*.product_variant_name' => 'required|string',
            'order_items.*.sku' => 'required|string',
            'order_items.*.unit_price' => 'required|numeric',
            'order_items.*.tax_rule' => 'required|numeric',
            'order_items.*.tax_rate' => 'required|numeric',
            'order_items.*.quantity' => 'required|numeric',

            'billing_address.first_name' => 'required|string|max:64',
            'billing_address.last_name' => 'required|string|max:64',
            'billing_address.first_phon' => 'required|string|max:64',
            'billing_address.last_phon' => 'required|string|max:64',
            'billing_address.country_code' => 'required|string|max:3',
            'billing_address.postal_code' => 'required|string|max:16',
            'billing_address.region_code' => 'required|string|max:16',
            'billing_address.region' => 'required|string|max:64',
            'billing_address.locality' => 'required|string|max:255',
            'billing_address.street' => 'required|string|max:255',
            'billing_address.phone_number' => 'required|string|max:64',

            'shipments' => 'required|array',
            'shipments.0' => 'required|array',

            'payments' => 'required|array',
            'payments.0.payment_method_id' => 'required|uuid|noblank',
        ],
    ];
}
