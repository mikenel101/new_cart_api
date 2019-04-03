<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use MikesLumenApi\Controllers\BaseController;
use MikesLumenBase\Traits\ApiHeaderTrait;
use App\Validators\CheckoutValidator;
use App\Repositories\CartRepository;
use App\Services\CartService;

class CheckoutController extends BaseController
{
    use ApiHeaderTrait;

    /**
     * @var App\Services\CartService
     */
    private $cartService;

    /**
     * @var App\Validators\CheckoutValidator
     */
    private $checkoutValidator;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(CartService $cartService, CheckoutValidator $checkoutValidator)
    {
        $this->cartService = $cartService;
        $this->checkoutValidator = $checkoutValidator;
    }


    private function getHeaders(Request $request)
    {
        $headers = $this->createHeadersForForwarding($request, getenv('APP_NAME'));
        $headers['X-ShopId'] = $this->getGuestShopId($request);
        return $headers;
    }


    /**
     * Estimate the value of a cart
     *
     * @param  Request $request
     *
     * @return \Dingo\Api\Http\Response
     */
    public function estimate(Request $request)
    {
        $shopId = $this->getGuestShopId($request);
        $inputOrder = $request->input();
        $inputOrder['shop_id'] = $shopId;
        $inputOrder['currency'] = $this->getCurrency($request);

        $this->checkoutValidator->with($inputOrder)->passesOrFail(CheckoutValidator::CART_CHECK_OUT_RULE);

        $headers = $this->getHeaders($request);
        $order = $this->cartService->buildOrder($inputOrder, $headers);

        return $this->response->array($order);
    }

    /**
     * Checkout a cart
     *
     * @param  Request $request
     *
     * @return \Dingo\Api\Http\Response
     */
    public function checkout(Request $request)
    {
        $shopId = $this->getGuestShopId($request);

        $inputOrder = $request->input();
        $inputOrder['shop_id'] = $shopId;
        $inputOrder['currency'] = $this->getCurrency($request);

        $this->checkoutValidator->with($inputOrder)->passesOrFail(CheckoutValidator::CART_CHECK_OUT_RULE);

        $headers = $this->getHeaders($request);
        $order = $this->cartService->buildOrder($inputOrder, $headers);

        $this->cartService->validateCartItemsOrFail($shopId, $inputOrder['order_items'], $headers);

        $this->cartService->validatePaymentsOrFail($order['payments'], $headers);

        $this->cartService->validateShipmentsOrFail($order['shipments'], $headers);

        $newOrder = $this->cartService->createOrder($order, $headers);

        // Remove cart session after check out sucessfully
        $cartRepository = new CartRepository();
        $cartKey = $this->getCartKey($request);
        $cartRepository->removeCart($cartKey);

        return $this->response->array($newOrder);
    }
}
