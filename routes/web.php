<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use App\Models\Coba;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Carbon;
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
                    $router->post('update/{id}', 'EtalaseController@update');
                    $router->delete('delete/{id}', 'EtalaseController@delete');
                });

                $router->group(['prefix' => 'product'], static function () use ($router) {
                    $router->post('create', 'ProductController@createProduct');
                    $router->post('edit/{product_id}', 'ProductController@updateProduct');
                    $router->delete('delete/{product_id}', 'ProductController@deleteProduct');
                    $router->post('stock/edit/{product_id}', 'ProductController@updateStockProduct');
                });

                $router->group(['prefix' => 'merchant'], static function () use ($router) {
                    $router->post('atur-toko', 'MerchantController@aturToko');
                    $router->post('set-expedition', 'MerchantController@setExpedition');
                    $router->post('atur-lokasi', 'MerchantController@aturLokasi');
                });

                $router->group(['prefix' => 'order'], static function () use ($router) {
                    $router->post('/{id}/approve', 'TransactionController@approveOrder');
                    $router->post('/{id}/reject', 'TransactionController@rejectOrder');
                    $router->post('/{id}/deliver', 'TransactionController@deliverOrder');
                    $router->post('accept', 'TransactionController@acceptOrder');
                    $router->post('reject/{order_id}', 'TransactionController@rejectOrder');
                    $router->post('awb-number/{order_id}/{awb}', 'TransactionController@addAwbNumberOrder');
                });

                $router->group(['prefix' => 'notification'], static function () use ($router) {
                    $router->post('/read/{id}', 'NotificationController@sellerReadNotification');
                    $router->delete('/delete/{id}', 'NotificationController@sellerDeleteNotification');
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
                    $router->get('merchant', 'ProductController@getProductByMerchantSeller');
                    $router->get('detail/{id}', 'ProductController@getProductById');
                    $router->get('etalase/{etalase_id}', 'ProductController@getProductByEtalase');
                    $router->get('search', 'ProductController@searchProductSeller');
                    $router->get('filter', 'ProductController@getProductByFilter');
                });

                $router->group(['prefix' => 'category'], static function () use ($router) {
                    $router->get('all', 'CategoryController@getAllCategory');
                });

                $router->group(['prefix' => 'transaction'], static function () use ($router) {
                    $router->get('/', 'TransactionController@sellerIndex');
                    $router->get('/new-order', 'TransactionController@newOrder');
                    $router->get('/to-deliver', 'TransactionController@orderToDeliver');
                    $router->get('/on-delivery', 'TransactionController@orderInDelivery');
                    $router->get('/done', 'TransactionController@orderDone');
                    $router->get('/canceled', 'TransactionController@sellerTransactionCanceled');
                    $router->get('/search', 'TransactionController@sellerSearchTransaction');
                    $router->get('/detail/{id}', 'TransactionController@detailTransaction');
                });

                $router->group(['prefix' => 'notification'], static function () use ($router) {
                    $router->get('/', 'NotificationController@sellerIndex');
                    $router->get('/list', 'NotificationController@sellerNotificationList');
                    $router->get('/list/{type}', 'NotificationController@sellerNotificationByType');
                });

                $router->group(['prefix' => 'region'], static function () use ($router) {
                    $router->get('search', 'RegionController@searchDistrict');
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
                $router->get('merchant/{merchant_id}', 'EtalaseController@publicEtalase');
            });

            $router->group(['prefix' => 'product'], static function () use ($router) {
                $router->get('recommend', 'ProductController@getRecommendProduct');
                $router->get('special', 'ProductController@getSpecialProduct');
                $router->get('search', 'ProductController@SearchProductByName');
                $router->get('merchant/{merchant_id}', 'ProductController@getProductByMerchantBuyer');
                $router->get('category/{category_id}', 'ProductController@getProductByCategory');
                $router->get('/merchant/{merchant_id}/featured', 'ProductController@getMerchantFeaturedProduct');
                $router->get('{id}', 'ProductController@getProductById');
            });

            $router->group(['prefix' => 'category'], static function () use ($router) {
                $router->get('/random', 'CategoryController@getThreeRandomCategory');
            });

            $router->group(['prefix' => 'setting'], static function () use ($router) {
                $router->get('profile', 'SettingProfileController@index');
            });

            $router->group(['prefix' => 'cart'], static function () use ($router) {
                $router->get('detail/{related_id}[/{buyer_id}]', 'CartController@showDetail');
                $router->get('{rlc_id}[/{buyer_id}]', 'CartController@index');
            });

            $router->group(['prefix' => 'region'], static function () use ($router) {
                $router->get('search', 'RegionController@searchDistrict');
            });

            $router->group(['prefix' => 'transaction'], static function () use ($router) {
                $router->get('/{related_id}', 'TransactionController@buyerIndex');
                $router->get('/{related_id}/detail/{id}', 'TransactionController@detailTransaction');
                $router->get('/{related_id}/on-payment', 'TransactionController@transactionToPay');
                $router->get('/{related_id}/on-approve', 'TransactionController@transactionOnApprove');
                $router->get('/{related_id}/on-delivery', 'TransactionController@transactionOnDelivery');
                $router->get('/{related_id}/done', 'TransactionController@buyerTransactionDone');
                $router->get('/{related_id}/canceled', 'TransactionController@buyerTransactionCanceled');
                $router->get('/{related_id}/search', 'TransactionController@buyerSearchTransaction');
                $router->get('/{related_id}/detail/{id}/invoice', 'TransactionController@getDetailInvoice');
            });

            $router->group(['prefix' => 'notification'], static function () use ($router) {
                $router->get('/{rlc_id}', 'NotificationController@buyerIndex');
                $router->get('/list/{rlc_id}', 'NotificationController@buyerNotificationList');
                $router->get('/list/{type}/{rlc_id}', 'NotificationController@buyerNotificationByType');
            });
        });
        $router->group(['prefix' => 'command'], static function () use ($router) {
            $router->group(['prefix' => 'order'], static function () use ($router) {
                $router->post('checkout/{customer_id}', 'TransactionController@checkout');
            });

            $router->group(['prefix' => 'cart'], static function () use ($router) {
                $router->post('add', 'CartController@add');
                $router->delete('delete/{cart_detail_id}/{cart_id}', 'CartController@destroy');
                $router->post('qty/update/{cart_detail_id}/{cart_id}', 'CartController@qtyUpdate');
                $router->delete('all/delete/{related_id}[/{buyer_id}]', 'CartController@deleteAllCart');
            });

            $router->group(['prefix' => 'notification'], static function () use ($router) {
                $router->post('/read/{id}/{rlc_id}', 'NotificationController@buyerReadNotification');
                $router->delete('/delete/{id}/{rlc_id}', 'NotificationController@buyerDeleteNotification');
            });

            $router->group(['prefix' => 'review'], static function () use ($router) {
                $router->post('add', 'ReviewController@addReview');
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
        $router->get('couriers', 'RajaOngkirController@couriers');
        $router->post('track', 'RajaOngkirController@trackOrder');
    });

    $router->group(['prefix' => 'order'], static function () use ($router) {
        $router->post('/{id}/request-cancel', 'TransactionController@requestCancelOrder');
        $router->post('/{id}/confirm', 'TransactionController@confirmOrder');
    });

    $router->group(['prefix' => 'merchant'], static function () use ($router) {
        $router->get('/list', 'MerchantController@requestMerchantList');
    });

    $router->get('/test', function(){
        return \App\Models\Product::wheredoesnthave('stock_active')->get();
    });
});
