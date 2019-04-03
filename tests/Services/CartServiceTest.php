<?php

use MikesLumenBase\TestUtils\MocksTrait;
use App\Services\CartService;
use App\Models\Product;

require_once (__DIR__ . '/../CartTestTrait.php');

class CartServiceTest extends TestCase
{
    use MocksTrait;
    use CartTestTrait;

    private $cartSerivce;

    public function setUp()
    {
        parent::setUp();
        $this->cartSerivce = app(CartService::class);
    }

    public function testValidateCartItemsOrFailOutStock()
    {
        $this->setExpectedException('MikesLumenApi\Exceptions\ValidatorException');

        $fetcher = $this->mockFetcher();
        $fetcher->shouldReceive('get')->andReturnUsing(function ($baseUri, $path, $body, $headers) {
            if ($path == 'product-variant-stocks') {
                return [[
                    'product_variant_id' => $this->productVariantId1,
                    'quantity' => 9
                ]];
            }
        });

        $cartItems = [$this->getCartItem1()];
        $isStockAvailable = $this->cartSerivce->validateCartItemsOrFail($this->shopId1, $cartItems, $this->getHeaders());
    }

    public function testValidateCartItemsOrFailMissingStock()
    {
        $this->setExpectedException('MikesLumenApi\Exceptions\ValidatorException');

        $fetcher = $this->mockFetcher();
        $fetcher->shouldReceive('get')->andReturnUsing(function ($baseUri, $path, $body, $headers) {
            if ($path == 'product-variant-stocks') {
                return [];
            }
        });

        $cartItems = [$this->getCartItem1()];
        $isStockAvailable = $this->cartSerivce->validateCartItemsOrFail($this->shopId1, $cartItems, $this->getHeaders());
    }

    public function testValidateCartItemsOrFailInStock()
    {
        $fetcher = $this->mockFetcher();
        $fetcher->shouldReceive('get')->andReturnUsing(function ($baseUri, $path, $body, $headers) {
            if ($path == 'product-variant-stocks') {
                return [[
                    'product_variant_id' => $this->productVariantId1,
                    'quantity' => 10
                ]];
            }
        });

        $cartItems = [$this->getCartItem1()];
        $isStockAvailable = $this->cartSerivce->validateCartItemsOrFail($this->shopId1, $cartItems, $this->getHeaders());
    }

    public function testValidateCartItemsOrFailOutStockMultiple()
    {
        $this->setExpectedException('MikesLumenApi\Exceptions\ValidatorException');

        $fetcher = $this->mockFetcher();
        $fetcher->shouldReceive('get')->andReturnUsing(function ($baseUri, $path, $body, $headers) {
            if ($path == 'product-variant-stocks') {
                return [[
                    'product_variant_id' => $this->productVariantId1,
                    'quantity' => 10
                ], [
                    'product_variant_id' => $this->productVariantId2,
                    'quantity' => 19
                ]];
            }
        });

        $cartItems = [$this->getCartItem1(), $this->getCartItem2()];
        $isStockAvailable = $this->cartSerivce->validateCartItemsOrFail($this->shopId1, $cartItems, $this->getHeaders());
    }


    public function testValidateCartItemsOrFailInStockMultiple()
    {
        $fetcher = $this->mockFetcher();
        $fetcher->shouldReceive('get')->andReturnUsing(function ($baseUri, $path, $body, $headers) {
            if ($path == 'product-variant-stocks') {
                return [[
                    'product_variant_id' => $this->productVariantId1,
                    'quantity' => 10
                ],[
                    'product_variant_id' => $this->productVariantId2,
                    'quantity' => 20
                ]];
            }
        });

        $cartItems = [$this->getCartItem1(), $this->getCartItem2()];
        $isStockAvailable = $this->cartSerivce->validateCartItemsOrFail($this->shopId1, $cartItems, $this->getHeaders());
    }

    public function testValidateCartItemsOrFailOnStockModeUnused()
    {
        $fetcher = $this->mockFetcher();
        $fetcher->shouldReceive('get')->andReturnUsing(function ($baseUri, $path, $body, $headers) {
            if ($path == 'product-variant-stocks') {
                return [[
                    'product_variant_id' => $this->productVariantId1,
                    'quantity' => 10
                ]];
            }
        });

        $cartItems = [$this->getCartItem1(), $this->getCartItem2()];
        $cartItems[1]['stock_mode'] = Product::STOCK_MODE_UNUSED;
        $isStockAvailable = $this->cartSerivce->validateCartItemsOrFail($this->shopId1, $cartItems, $this->getHeaders());
    }
}