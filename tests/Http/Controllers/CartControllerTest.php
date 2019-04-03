<?php

use Illuminate\Support\Facades\Cache;
use MikesLumenBase\TestUtils\MocksTrait;
use MikesLumenBase\TestUtils\ArrayAssertTrait;
use App\Models\Product;


require_once (__DIR__ . '/../../CartTestTrait.php');

class CartControllerTest extends TestCase
{
    use MocksTrait;
    use CartTestTrait;
    use ArrayAssertTrait;

    public function setUp()
    {
        parent::setUp();
        Cache::flush();

        $fetcher = $this->mockFetcher();
        $fetcher->shouldReceive('get')->andReturnUsing(function ($baseUri, $path, $body, $headers) {
            if ($path == 'product-variant-stocks') {
                return [[
                    'product_variant_id' => $this->productVariantId1,
                    'quantity' => 10000
                ], [
                    'product_variant_id' => $this->productVariantId2,
                    'quantity' => 10000
                ]];
            }
        });
    }

    public function testShowForEmpty()
    {
        $this->get('carts/self', $this->getHeaders());
        $this->assertResponseOk();
        $content = json_decode($this->response->getContent());
        $this->assertEmpty($content->cart_items);
    }

    public function testStoreItem()
    {
        $cartItem1 = $this->getCartItem1();
        $this->post('carts/self/cart-items', $cartItem1, $this->getHeaders());
        $this->assertResponseOk();

        $this->get('carts/self', $this->getHeaders());
        $this->assertResponseOk();
        $content = json_decode($this->response->getContent());
        $this->assertEquals(1, count($content->cart_items));
        $this->assertArrayPartialMatch($cartItem1, (array) $content->cart_items[0]);
    }

    public function testStoreItemTwice()
    {
        $cartItem1 = $this->getCartItem1();
        $this->post('carts/self/cart-items', $cartItem1, $this->getHeaders());
        $this->assertResponseOk();

        $this->post('carts/self/cart-items', $cartItem1, $this->getHeaders());
        $this->assertResponseOk();

        $this->get('carts/self', $this->getHeaders());
        $this->assertResponseOk();
        $content = json_decode($this->response->getContent());
        $this->assertEquals(1, count($content->cart_items));

        $cartItem1['quantity'] *= 2;
        $this->assertArrayPartialMatch($cartItem1, (array) $content->cart_items[0]);
    }

    public function testStoreItemMultiple()
    {
        $cartItem1 = $this->getCartItem1();
        $this->post('carts/self/cart-items', $cartItem1, $this->getHeaders());
        $this->assertResponseOk();

        $cartItem2 = $this->getCartItem2();
        $this->post('carts/self/cart-items', $cartItem2, $this->getHeaders());
        $this->assertResponseOk();

        $this->get('carts/self', $this->getHeaders());
        $this->assertResponseOk();
        $content = json_decode($this->response->getContent());
        $this->assertEquals(2, count($content->cart_items));

        $this->assertArrayPartialMatch($cartItem1, (array) $content->cart_items[0]);
        $this->assertArrayPartialMatch($cartItem2, (array) $content->cart_items[1]);
    }

    public function testRemoveItem()
    {
        $cartItem1 = $this->getCartItem1();
        $this->post('carts/self/cart-items', $cartItem1, $this->getHeaders());
        $this->assertResponseOk();

        $cartItem2 = $this->getCartItem2();
        $this->post('carts/self/cart-items', $cartItem2, $this->getHeaders());
        $this->assertResponseOk();

        $this->delete('carts/self/cart-items/' . $this->productVariantId2, $this->getHeaders());
        $this->assertResponseOk();

        $this->get('carts/self', $this->getHeaders());
        $this->assertResponseOk();
        $content = json_decode($this->response->getContent());
        $this->assertEquals(1, count($content->cart_items));

        $this->assertArrayPartialMatch($cartItem1, (array) $content->cart_items[0]);

        $this->delete('carts/self/cart-items/' . $this->productVariantId1, $this->getHeaders());
        $this->assertResponseOk();

        $this->get('carts/self', $this->getHeaders());
        $this->assertResponseOk();
        $content = json_decode($this->response->getContent());
        $this->assertEmpty($content->cart_items);
    }

    public function testRemoveItemForNotExsits()
    {
        $this->delete('carts/self/cart-items/' . $this->productVariantId1, $this->getHeaders());
        $this->assertResponseStatus(400);
    }

    public function testDestroy()
    {
        $cartItem1 = $this->getCartItem1();
        $this->post('carts/self/cart-items', $cartItem1, $this->getHeaders());
        $this->assertResponseOk();

        $cartItem2 = $this->getCartItem2();
        $this->post('carts/self/cart-items', $cartItem2, $this->getHeaders());
        $this->assertResponseOk();

        $this->delete('carts/self', $this->getHeaders());
        $this->assertResponseStatus(204);

        $this->get('carts/self', $this->getHeaders());
        $this->assertResponseOk();
        $content = json_decode($this->response->getContent());
        $this->assertEmpty($content->cart_items);
    }
}
