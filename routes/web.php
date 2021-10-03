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

$router->group(['prefix' => 'v1', 'namespace' => 'Api\V1'], function () use ($router) {
    $router->group(['prefix' => 'seller'], static function () use ($router) {
        $router->group(['middleware' => 'auth'], function () use ($router) {
            $router->group(['prefix' => 'command'], static function () use ($router) {
                $router->post('store', 'EtalaseController@store');
                $router->group(['prefix' => 'etalase'], static function () use ($router) {
                    $router->delete('delete/{id}', 'EtalaseController@delete');
                });
                $router->group(['prefix' => 'product'], static function () use ($router) {
                    $router->post('create', 'ProductController@createProduct');
                    $router->post('edit/{product_id}/{merchant_id}', 'ProductController@updateProduct');
                    $router->delete('delete/{product_id}/{merchant_id}', 'ProductController@deleteProduct');
                    $router->post('stock/edit/{product_id}/{merchant_id}', 'ProductController@updateStockProduct');
                });
            });
            $router->group(['prefix' => 'query'], static function () use ($router) {
                $router->group(['prefix' => 'etalase'], static function () use ($router) {
                    $router->get('/', 'EtalaseController@index');
                    $router->get('show/{id}', 'EtalaseController@show');
                });
                $router->group(['prefix' => 'product'], static function () use ($router) {
                    $router->get('all', 'ProductController@getAllProduct');
                    $router->get('merchant/{merchant_id}', 'ProductController@getProductByMerchantSeller');
                    $router->get('etalase/{etalase_id}', 'ProductController@getProductByEtalase');
                });
                $router->group(['prefix' => 'category'], static function () use ($router) {
                    $router->get('all', 'CategoryController@getAllCategory');
                });
            });
        });
    });
    $router->group(['prefix' => 'buyer'], static function () use ($router) {
        $router->group(['prefix' => 'query'], static function () use ($router) {
            $router->group(['prefix' => 'etalase'], static function () use ($router) {

            });
            $router->group(['prefix' => 'product'], static function () use ($router) {
                $router->get('search/{keyword}', 'ProductController@SearchProductByName');
                $router->get('merchant/{merchant_id}', 'ProductController@getProductByMerchantBuyer');
                $router->get('category/{category_id}', 'ProductController@getProductByCategory');
                $router->get('{id}', 'ProductController@getProductById');
            });
            $router->group(['prefix' => 'category'], static function () use ($router) {
                $router->get('/random', 'CategoryController@getThreeRandomCategory');
            });
        });
    });

    $router->group(['prefix' => 'profile', 'middleware' => 'auth'], static function () use ($router) {
        $router->get('user', 'ProfileController@index');
        $router->post('logout', 'ProfileController@logout');
    });


});
