<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use MikesLumenApi\Controllers\BaseController;
use MikesLumenBase\Traits\ApiHeaderTrait;
use App\Validators\CartValidator;
use App\Repositories\CartRepository;
use App\Services\CartService;
use App\Models\Product;

class CartController extends BaseController
{
    use ApiHeaderTrait;

    /**
     * @var string $cartKey key of cart in cache
     */
    private $cartKey;

    /**
     * @var App\Repositories\CartRepository
     */
    private $cartRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        Request $request,
        CartRepository $cartRepository
    ) {
        $this->cartRepository = $cartRepository;
        $this->cartKey = $this->getCartKey($request);
    }

    private function getHeaders(Request $request)
    {
        $headers = $this->createHeadersForForwarding($request, getenv('APP_NAME'));
        $headers['X-ShopId'] = $this->getGuestShopId($request);
        return $headers;
    }

    /**
     * View cart detail
     *
     * @param Request $request
     *
     * @return \Dingo\Api\Http\Response
     */
    public function show(Request $request)
    {
        $cart = $this->cartRepository->getCart($this->cartKey);
        return $this->response->array($cart);
    }


    /**
     * Add product item to cart
     *
     * @param Request $request
     *
     * @return \Dingo\Api\Http\Response
     */
    public function storeItem(Request $request)
    {
        $inputCartItem = $request->only(
            'product_id',
            'product_variant_id',
            'sku',
            'quantity',
            'stock_mode',
            'unit_price',
            'product_image',
            'product_name',
            'product_variant_name',
            'tax_rule',
            'tax_rate'
        );

        $cartValidator = new CartValidator();
        $cartValidator->with($inputCartItem)->passesOrFail(CartValidator::ADD_CART_ITEM_RULE);

        $shopId = $this->getGuestShopId($request);

        if (empty($this->cartKey)) {
            $this->cartKey = $this->cartRepository->generateCartKey();
        }

        $productVariantId = $inputCartItem['product_variant_id'];
        $quantity = (int) $inputCartItem['quantity'];

        $cart = $this->cartRepository->getCart($this->cartKey);
        $cartItems = $cart['cart_items'];

        $newCartItem = array();
        foreach ($cartItems as $key => $cartItem) {
            if ($cartItem['product_variant_id'] == $productVariantId) {
                $newCartItem = array_merge($inputCartItem, $cartItem);
                $newCartItem['quantity'] = $cartItems[$key]['quantity'] + $quantity;
                if ($newCartItem['quantity'] > 0) {
                    $cartItems[$key] = $newCartItem;
                } else {
                    unset($cartItems[$key]);
                    $cartItems = array_values($cartItems);
                }
                break;
            }
        }

        if (empty($newCartItem)) {
            $newCartItem = array_merge($inputCartItem, [
                'quantity' => $quantity
            ]);
            $cartItems[] = $newCartItem;
        }

        $usesStock = $inputCartItem['stock_mode'] == Product::STOCK_MODE_USE;
        if ($usesStock && $newCartItem['quantity'] > 0) {
            $headers = $this->getHeaders($request);
            $cartService = new CartService();
            $cartService->validateCartItemsOrFail($shopId, [$newCartItem], $headers);
        }

        $cart['cart_items'] = $cartItems;
        $this->cartRepository->putCart($this->cartKey, $cart);

        return $this->response->array($cart);
    }

    /**
     * Remove an item from cart
     *
     * @param string $productVariantId
     * @param Request $request
     *
     * @return \Dingo\Api\Http\Response
     */
    public function removeItem(Request $request, $productVariantId)
    {
        $inputCartItem = ['product_variant_id' => $productVariantId];
        $cartValidator = new CartValidator();
        $cartValidator->with($inputCartItem)->passesOrFail(CartValidator::REMOVE_CART_ITEM_RULE);

        $cart = $this->cartRepository->getCart($this->cartKey);
        $cartItems = $cart['cart_items'];

        $deleted = false;
        foreach ($cartItems as $key => $cartItem) {
            if ($cartItem['product_variant_id'] == $productVariantId) {
                unset($cartItems[$key]);
                $cartItems = array_values($cartItems);
                $deleted = true;
                break;
            }
        }

        if (!$deleted) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException(trans('message.remove_cart_fail'));
        }

        $cart['cart_items'] = $cartItems;
        $this->cartRepository->putCart($this->cartKey, $cart);

        return $this->response->array($cart);
    }

    /**
     * Make cart empty
     *
     * @param Request $request
     *
     * @return \Dingo\Api\Http\Response
     */
    public function destroy(Request $request)
    {
        $this->cartRepository->removeCart($this->cartKey);

        return $this->noContent();
    }
}
