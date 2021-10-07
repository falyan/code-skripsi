<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

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
            $router->group(['prefix' => 'command', 'middleware' => 'auth'], static function () use ($router) {
                $router->group(['prefix' => 'etalase', 'middleware' => 'auth'], static function () use ($router) {
                    $router->post('store', 'EtalaseController@store');
                    $router->delete('delete/{id}', 'EtalaseController@delete');
                });

                $router->group(['prefix' => 'product'], static function () use ($router) {
                    $router->post('create', 'ProductController@createProduct');
                    $router->post('edit/{product_id}/{merchant_id}', 'ProductController@updateProduct');
                    $router->delete('delete/{product_id}/{merchant_id}', 'ProductController@deleteProduct');
                    $router->post('stock/edit/{product_id}/{merchant_id}', 'ProductController@updateStockProduct');
                });

                $router->group(['prefix' => 'merchant'], static function () use ($router) {
                    $router->post('atur-toko', 'MerchantController@aturToko');
                });
            });

            $router->group(['prefix' => 'query'], static function () use ($router) {
                $router->group(['prefix' => 'merchant'], static function () use ($router) {
                    $router->get('profile-toko', 'MerchantController@homepageProfile');
                });

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

                $router->group(['prefix' => 'transaction'], static function () use ($router) {
                    $router->get('/', 'TransactionController@sellerIndex');
                    $router->get('/detail/{id}', 'TransactionController@detailTransaction');
                    $router->get('/new-order', 'TransactionController@newOrder');
                    $router->get('/to-deliver', 'TransactionController@orderToDeliver');
                    $router->get('/on-delivery', 'TransactionController@orderInDelivery');
                    $router->get('/done', 'TransactionController@orderDone');
                    $router->get('/calceled', 'TransactionController@sellerTransactionCanceled');
                    $router->get('/search/{keyword}', 'TransactionController@sellerSearchTransaction');
                });
            });
        });
    });

    $router->group(['prefix' => 'buyer'], static function () use ($router) {
        $router->group(['prefix' => 'query'], static function () use ($router) {

            $router->group(['prefix' => 'merchant'], static function () use ($router) {
                $router->get('{merchant_id}', 'MerchantController@publicProfile');
            });

            $router->group(['prefix' => 'etalase'], static function () use ($router) {
                $router->get('rajaongkir', 'EtalaseController@rajaongkir');
            });

            $router->group(['prefix' => 'product'], static function () use ($router) {
                $router->get('recommend', 'ProductController@getRecommendProduct');
                $router->get('special', 'ProductController@getSpecialProduct');
                $router->get('search/{keyword}[/{limit}]', 'ProductController@SearchProductByName');
                $router->get('merchant/{merchant_id}', 'ProductController@getProductByMerchantBuyer');
                $router->get('category/{category_id}', 'ProductController@getProductByCategory');
                $router->get('{id}', 'ProductController@getProductById');
            });

            $router->group(['prefix' => 'category'], static function () use ($router) {
                $router->get('/random', 'CategoryController@getThreeRandomCategory');
            });

            $router->group(['prefix' => 'setting'], static function () use ($router) {
                $router->get('profile', 'SettingProfileController@index');
            });

            $router->group(['prefix' => 'cart'], static function () use ($router) {
                $router->get('/', 'CartController@index');
                $router->get('detail/{related_id}[/{buyer_id}]', 'CartController@showDetail');
            });

            $router->group(['prefix' => 'region'], static function () use ($router) {
                $router->get('search/{keyword}[/{limit}]', 'RegionController@searchDistrict');
            });

            $router->group(['prefix' => 'transaction'], static function () use ($router) {
                $router->get('/', 'TransactionController@buyerIndex');
                $router->get('/detail/{id}', 'TransactionController@detailTransaction');
                $router->get('/on-payment', 'TransactionController@transactionToPay');
                $router->get('/on-approve', 'TransactionController@transactionOnApprove');
                $router->get('/on-delivery', 'TransactionController@transactionOnDelivery');
                $router->get('/done', 'TransactionController@buyerTransactionDone');
                $router->get('/calceled', 'TransactionController@buyerTransactionCanceled');
                $router->get('/search/{keyword}', 'TransactionController@buyerSearchTransaction');
            });
        });
        $router->group(['prefix' => 'command'], static function () use ($router) {
            $router->group(['prefix' => 'cart'], static function () use ($router) {
                $router->post('add', 'CartController@add');
                $router->delete('delete', 'CartController@destroy');
                $router->patch('qty/update', 'CartController@qtyUpdate');
            });
        });
    });

    $router->group(['prefix' => 'setting'], static function () use ($router) {
        $router->get('faq', 'FaqController@index');
        $router->group(['prefix' => 'pages'], static function () use ($router) {
            $router->get('term-condition', 'PagesController@termCondition');
            $router->get('contact-us', 'PagesController@contactUs');
            $router->get('about-us', 'PagesController@aboutUs');
            $router->get('privacy-policy', 'PagesController@privacyPolicy');
        });
    });

    $router->group(['prefix' => 'profile', 'middleware' => 'auth'], static function () use ($router) {
        $router->get('user', 'ProfileController@index');
        $router->post('logout', 'ProfileController@logout');
    });

    $router->group(['prefix' => 'rajaongkir'], static function () use ($router) {
        $router->get('province', 'RajaOngkirController@getProvince');
        $router->get('district', 'RajaOngkirController@getDistrict');
        $router->post('ongkir', 'RajaOngkirController@ongkir');
    });
});
