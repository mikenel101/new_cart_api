<?php

namespace App\Repositories;

use Cache;

class CartRepository
{
    const CART_EXPIRATION = 86400;

    protected function getCartExpiration()
    {
        return self::CART_EXPIRATION;
    }

    /**
     * @return string
     */
    public function generateCartKey()
    {
        return 'mikecart-' . bin2hex(\Webpatser\Uuid\Uuid::generate(4)->bytes);
    }


    /**
     * @param  string $cartKey
     * @return array
     */
    public function getCart(?string $cartKey)
    {
        if (empty($cartKey)) {
            return ['cart_items' => []];
        }

        $cart = Cache::get($cartKey, []);
        $cart['cart_key'] = $cartKey;
        if (!isset($cart['cart_items'])) {
            $cart['cart_items'] = [];
        }

        return $cart;
    }

    /**
     * @param  string $cartKey
     * @param  array  $cart
     * @param  int    $cartExpiration
     *
     * @return void
     */
    public function putCart(string $cartKey, array $cart)
    {
        unset($cart['cart_key']);

        Cache::put($cartKey, $cart, $this->getCartExpiration());
    }

    /**]
     * @param  string $cartKey
     *
     * @throws \MikesLumenApi\Exceptions\BadRequestException
     *
     * @return void
     */
    public function removeCart(?string $cartKey)
    {
        if (!$cartKey) {
            return;
        }

        $success = Cache::forget($cartKey);
        if (!$success) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException(trans('message.remove_cart_fail'));
        }
    }
}
