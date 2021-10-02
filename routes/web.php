<?php

/** @var \Laravel\Lumen\Routing\Router $router */

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

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['domain' => env('API_DOMAIN'),
    'prefix' => env('API_PREFIX', 'api')], function () use ($router){
        $router->post('product/create', 'ProductController@createProduct');
        $router->post('product/edit/{product_id}/{merchant_id}', 'ProductController@updateProduct');
        $router->delete('product/delete/{product_id}/{merchant_id}', 'ProductController@deleteProduct');
        $router->get('product/all', 'ProductController@getAllProduct');
        $router->get('product/merchant/{merchant_id}', 'ProductController@getProductByMerchant');
        $router->get('product/etalase/{etalase_id}', 'ProductController@getProductByEtalase');
        $router->post('product/stock/edit/{product_id}/{merchant_id}', 'ProductController@updateStockProduct');
});
