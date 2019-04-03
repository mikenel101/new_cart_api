<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$api = $app['api.router'];

$api->version(['v1'], ['namespace' => 'App\Http\Controllers'], function ($api) {
    $api->get('/', function () {
        return config('app.vendor') . '/' . config('app.group') . '_' . config('app.name') . ':' . config('app.version');
    });

    $api->get('carts/self', 'CartController@show');
    $api->post('carts/self/cart-items', 'CartController@storeItem');
    $api->delete('carts/self/cart-items/{productVariantId}', 'CartController@removeItem');
    $api->delete('carts/self', 'CartController@destroy');
    $api->post('carts/self/estimate', 'CheckoutController@estimate');
    $api->post('carts/self/checkout', 'CheckoutController@checkout');
});
