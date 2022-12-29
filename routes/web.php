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
    $router->post('biller/payment/notification', 'TransactionController@updatePaymentStatus');
    $router->post('biller/payment/notification-bot', 'TransactionController@updatePaymentStatusForBOT');
    $router->post('trigger/all', 'TransactionController@triggerRatingProductSold');

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
                    $router->post('price/edit/{product_id}', 'ProductController@updatePriceProduct');
                    $router->post('featured/edit/', 'ProductController@updateProductFeatured');
                    $router->post('archived/{product_id}', 'ProductController@updateProductArchived');
                });

                $router->group(['prefix' => 'merchant'], static function () use ($router) {
                    $router->post('atur-toko', 'MerchantController@aturToko');
                    $router->post('set-expedition', 'MerchantController@setExpedition');
                    $router->post('atur-lokasi', 'MerchantController@aturLokasi');
                    $router->post('update', 'MerchantController@updateMerchantProfile');
                    $router->post('set-customlogistic', 'MerchantController@setCustomLogistic');
                    $router->get('banner', 'MerchantController@getBanner');
                    $router->post('banner', 'MerchantController@createBanner');
                    $router->delete('banner/{banner_id}', 'MerchantController@deleteBanner');
                });

                $router->group(['prefix' => 'order'], static function () use ($router) {
                    $router->post('/{id}/approve', 'TransactionController@approveOrder');
                    $router->post('/{id}/reject', 'TransactionController@rejectOrder');
                    $router->post('/{id}/deliver', 'TransactionController@deliverOrder');
                    $router->post('accept', 'TransactionController@acceptOrder');
                    $router->post('reject/{order_id}', 'TransactionController@rejectOrder');
                    $router->post('awb-number/{order_id}/{awb}', 'TransactionController@addAwbNumberOrder');
                    $router->post('generate-resi/{order_id}', 'TransactionController@addAwbNumberAutoOrder');
                    $router->post('pesanan-sampai/{order_id}', 'TransactionController@orderConfirmHasArrived');
                });

                $router->group(['prefix' => 'notification'], static function () use ($router) {
                    $router->post('/read/{id}', 'NotificationController@sellerReadNotification');
                    $router->delete('/delete/{id}', 'NotificationController@sellerDeleteNotification');
                });

                $router->group(['prefix' => 'review', 'middleware' => 'auth'], static function () use ($router) {
                    $router->post('reply/{review_id}', 'ReviewController@replyReview');
                });

                $router->group(['prefix' => 'testdrive', 'middleware' => 'auth'], static function () use ($router) {
                    $router->post('create', 'TestDriveController@create');
                    $router->post('cancel/{id}', 'TestDriveController@cancel');
                    $router->post('booking/approve', 'TestDriveController@approveBooking');
                    $router->post('booking/reject', 'TestDriveController@rejectBooking');
                    $router->post('booking/buyer/{event_id}', 'TestDriveController@buyerBookingFromMerchant');
                });

                $router->group(['prefix' => 'discussion'], static function () use ($router) {
                    $router->post('reply', 'DiscussionController@replyBuyerDiscussion');
                    $router->post('read/{id}', 'DiscussionController@sellerReadDiscussion');
                });
            });

            $router->group(['prefix' => 'query'], static function () use ($router) {
                $router->group(['prefix' => 'merchant'], static function () use ($router) {
                    $router->get('profile-toko', 'MerchantController@homepageProfile');
                    $router->get('activity', 'MerchantController@activity');
                });

                $router->group(['prefix' => 'etalase'], static function () use ($router) {
                    $router->get('/', 'EtalaseController@index');
                    $router->get('show/{id}', 'EtalaseController@show');
                });

                $router->group(['prefix' => 'product'], static function () use ($router) {
                    $router->get('merchant', 'ProductController@getProductByMerchantSeller');
                    $router->get('best-selling', 'ProductController@getBestSellingProductByMerchant');
                    $router->get('almost-running-out', 'ProductController@getProductAlmostRunningOut');
                    $router->get('detail/{id}', 'ProductController@getProductByIdSeller');
                    $router->get('etalase/{etalase_id}', 'ProductController@getProductByEtalase');
                    $router->get('search', 'ProductController@searchProductSeller');
                    $router->post('searchv2', 'ProductController@searchProductSellerV2');
                    $router->post('filter', 'ProductController@getProductByFilter');
                    $router->post('filter/count', 'ProductController@countProductByFilter');
                    $router->get('featured', 'ProductController@getProductFeatured');
                });

                $router->group(['prefix' => 'category'], static function () use ($router) {
                    $router->get('all', 'CategoryController@getAllCategory');
                });

                $router->group(['prefix' => 'approval'], static function () use ($router) {
                    $router->get('category/{category_key}', 'ProductCategoryApprovalController@checkCategory');
                });

                $router->group(['prefix' => 'variant'], static function () use ($router) {
                    $router->get('category/{category_id}', 'VariantController@getVariantByCategory');
                });

                $router->group(['prefix' => 'transaction'], static function () use ($router) {
                    $router->get('/', 'TransactionController@sellerIndex');
                    $router->get('/new-order', 'TransactionController@newOrder');
                    $router->get('/to-deliver', 'TransactionController@orderToDeliver');
                    $router->get('/on-delivery', 'TransactionController@orderInDelivery');
                    $router->get('/done', 'TransactionController@orderDone');
                    $router->get('/canceled', 'TransactionController@sellerTransactionCanceled');
                    $router->get('/search', 'TransactionController@sellerSearchTransaction');
                    $router->get('/search/count', 'TransactionController@sellerCountSearchTransaction');
                    $router->get('/detail/{id}', 'TransactionController@detailTransaction');
                    $router->get('/export/excel', 'TransactionController@exportExcel');
                });

                $router->group(['prefix' => 'notification'], static function () use ($router) {
                    $router->get('/', 'NotificationController@sellerIndex');
                    $router->get('/list', 'NotificationController@sellerNotificationList');
                    $router->get('/list/{type}', 'NotificationController@sellerNotificationByType');
                });

                $router->group(['prefix' => 'region'], static function () use ($router) {
                    $router->get('search', 'RegionController@searchDistrict');
                });

                $router->group(['prefix' => 'review', 'middleware' => 'auth'], static function () use ($router) {
                    $router->get('list', 'ReviewController@getListReviewByMerchant');
                    $router->get('list/done', 'ReviewController@getListReviewDoneByMerchant');
                    $router->get('list/done/reply', 'ReviewController@getListReviewDoneReplyByMerchant');
                    $router->get('list/done/unreply', 'ReviewController@getListReviewDoneUnreplyByMerchant');
                    $router->get('detail/{review_id}', 'ReviewController@getDetailReview');
                });

                $router->group(['prefix' => 'testdrive', 'middleware' => 'auth'], static function () use ($router) {
                    $router->get('list-ev', 'TestDriveController@getEVProducts');
                    $router->get('detail/{id}', 'TestDriveController@getDetail');
                    $router->get('list-booking/{id}', 'TestDriveController@getBookingList');
                    $router->get('history', 'TestDriveController@getHistoryBySeller');
                    $router->get('list-active', 'TestDriveController@getListActiveEventBySeller');
                    $router->get('list-all', 'TestDriveController@getListActiveEvent');
                    $router->get('list-peserta', 'TestDriveController@getListPeserta');
                    $router->get('peserta/{id}', 'TestDriveController@getListPesertaById');
                });

                $router->group(['prefix' => 'mdr'], static function () use ($router) {
                    $router->get('value/{category_id}', 'MdrController@getMdrValue');
                });

                $router->group(['prefix' => 'discussion'], static function () use ($router) {
                    $router->post('list/all', 'DiscussionController@getListAllDiscussionBySeller');
                    $router->post('list/unread', 'DiscussionController@getListUnreadDiscussionBySeller');
                    $router->post('list/read', 'DiscussionController@getListReadDiscussionBySeller');
                    $router->get('detail/{id}', 'DiscussionController@getDiscussionByMasterId');
                });
            });
        });
    });

    $router->group(['prefix' => 'buyer'], static function () use ($router) {
        $router->group(['prefix' => 'query'], static function () use ($router) {
            $router->group(['prefix' => 'address'], static function () use ($router) {
                $router->group(['middleware' => 'auth'], static function () use ($router) {
                    $router->get('list', 'CustomerController@getListCustomerAddress');
                    $router->get('default', 'CustomerController@getDefaultCustomerAddress');
                });
            });

            $router->group(['prefix' => 'merchant'], static function () use ($router) {
                $router->get('/official-store', 'MerchantController@getOfficialStore');
                $router->get('/official-store/search', 'MerchantController@searchOfficialStoreByName');
                $router->get('{merchant_id}', 'MerchantController@publicProfile');
                $router->get('v2/{merchant_id}', 'MerchantController@publicProfileV2');
                $router->get('/official/{category_key}', 'MerchantController@getOfficialMerchant');
                $router->get('/official/{category_key}/{sub_category_key}', 'MerchantController@getOfficialMerchantBySubCategory');
            });

            $router->group(['prefix' => 'category'], static function () use ($router) {
                $router->get('basic/all', 'CategoryController@getBasicCategory');
                $router->get('parent/electric_vehicle', 'CategoryController@getParentCategory');
                $router->get('child/electric_vehicle', 'CategoryController@getChildCategory');
            });

            $router->group(['prefix' => 'etalase'], static function () use ($router) {
                $router->get('merchant/{merchant_id}', 'EtalaseController@publicEtalase');
                $router->get('merchant/{merchant_id}/etalase/{etalase_id}', 'EtalaseController@publicEtalaseMerchant');
            });

            $router->group(['prefix' => 'product'], static function () use ($router) {
                $router->get('all', 'ProductController@getAllProduct');
                $router->get('recommend', 'ProductController@getRecommendProduct');
                $router->get('special', 'ProductController@getSpecialProduct');
                $router->get('search', 'ProductController@searchProductByName');
                $router->post('searchv2', 'ProductController@searchProductByNameV2');
                $router->get('merchant/{merchant_id}', 'ProductController@getProductByMerchantBuyer');
                $router->get('merchant/{merchant_id}/search', 'ProductController@getProductByMerchantIdBuyerAndSearch');
                $router->get('category/{category_id}', 'ProductController@getProductByCategory');
                $router->get('ev/others', 'ProductController@getOtherEvProduct');
                $router->get('ev/others/{category_id}', 'ProductController@getOtherEvProductByCategory');
                $router->get('/merchant/{merchant_id}/featured', 'ProductController@getMerchantFeaturedProduct');
                $router->get('{id}', 'ProductController@getProductById');
                $router->get('recommend/category/{category_key}', 'ProductController@getRecommendProductByCategory');
                $router->get('review/{product_id}', 'ProductController@getReviewByProduct');
                $router->get('official/{category_key}/{sub_category_key}', 'ProductController@getElectricVehicleByCategory');
                $router->get('official/{category_key}/{sub_category_key}/{id}', 'ProductController@getElectricVehicleWithCategoryById');
                $router->post('filter', 'ProductController@getProductWithFilter');
                $router->post('filter/count', 'ProductController@countProductWithFilter');
                $router->post('check/stock', 'ProductController@checkProductStock');
            });

            $router->group(['prefix' => 'variant'], static function () use ($router) {
                $router->get('detail/{variant_value_id}/product', 'VariantController@getVariantByProduct');
            });

            $router->group(['prefix' => 'category'], static function () use ($router) {
                $router->get('/random', 'CategoryController@getThreeRandomCategory');
                $router->get('/all', 'CategoryController@getAllCategory');
            });

            $router->group(['prefix' => 'setting'], static function () use ($router) {
                $router->get('profile', 'SettingProfileController@index');
            });

            $router->group(['prefix' => 'cart'], static function () use ($router) {
                $router->get('/', 'CartController@index');

                $router->group(['middleware' => 'auth'], static function () use ($router) {
                    $router->get('detail/{related_id}[/{buyer_id}]', 'CartController@showDetail');
                });
            });

            $router->group(['prefix' => 'region'], static function () use ($router) {
                $router->get('search', 'RegionController@searchDistrict');
                $router->post('search/province', 'RegionController@searchProvince');
                $router->post('search/city', 'RegionController@searchCity');
            });

            $router->group(['prefix' => 'checkout', 'middleware' => 'auth'], static function () use ($router) {
                $router->post('/count', 'TransactionController@countCheckoutPrice');
            });

            $router->group(['prefix' => 'transaction', 'middleware' => 'auth'], static function () use ($router) {
                $router->get('/delivery-discount', 'TransactionController@getDeliveryDiscount');
                $router->get('/customer-discount', 'TransactionController@getCustomerDiscount');
                $router->get('/{related_id}', 'TransactionController@buyerIndex');

                $router->get('/{related_id}/category/{category_key}', 'TransactionController@transactionByCategoryKey');

                $router->get('/{related_id}/detail/{id}', 'TransactionController@detailTransaction');
                $router->get('/{related_id}/on-payment', 'TransactionController@transactionToPay');
                $router->get('/{related_id}/on-approve', 'TransactionController@transactionOnApprove');
                $router->get('/{related_id}/on-delivery', 'TransactionController@transactionOnDelivery');
                $router->get('/{related_id}/done', 'TransactionController@buyerTransactionDone');
                $router->get('/{related_id}/canceled', 'TransactionController@buyerTransactionCanceled');
                $router->get('/{related_id}/search', 'TransactionController@buyerSearchTransaction');
                $router->get('/{related_id}/detail/{id}/invoice', 'TransactionController@getDetailInvoice');
                $router->get('/{related_id}/on-process', 'TransactionController@transactionOnProccess');
            });

            $router->group(['prefix' => 'notification', 'middleware' => 'auth'], static function () use ($router) {
                $router->get('/{rlc_id}', 'NotificationController@buyerIndex');
                $router->get('/list/{rlc_id}', 'NotificationController@buyerNotificationList');
                $router->get('/list/{type}/{rlc_id}', 'NotificationController@buyerNotificationByType');
            });

            $router->group(['prefix' => 'review', 'middleware' => 'auth'], static function () use ($router) {
                $router->get('list', 'ReviewController@getListReviewByBuyer');
                $router->get('list/done', 'ReviewController@getListReviewDoneByBuyer');
                $router->get('list/undone', 'ReviewController@getListReviewUndoneByBuyer');
                $router->get('list/transaction/{trx_id}', 'ReviewController@getListReviewByTransaction');
                $router->get('detail/{review_id}', 'ReviewController@getDetailReview');
            });

            $router->group(['prefix' => 'wishlist', 'middleware' => 'auth'], static function () use ($router) {
                $router->get('list', 'WishlistController@getListWishlistByCustomer');
                $router->get('search', 'WishlistController@searchListWishlistByName');
            });

            $router->group(['prefix' => 'testdrive', 'middleware' => 'auth'], static function () use ($router) {
                $router->get('list', 'TestDriveController@getAllActiveEvent');
                $router->get('detail/{id}', 'TestDriveController@getDetail');
                $router->get('history', 'TestDriveController@getHistoryByCustomer');
                $router->get('history/detail/{id}', 'TestDriveController@getDetailBooking');
                $router->get('merchant/{merchant_id}', 'TestDriveController@getListActiveEventByMerchant');
            });

            $router->group(['prefix' => 'discussion', 'middleware' => 'auth'], static function () use ($router) {
                $router->post('list/all', 'DiscussionController@getListAllDiscussionByBuyer');
                $router->post('list/unread', 'DiscussionController@getListUnreadDiscussionByBuyer');
                $router->post('list/read', 'DiscussionController@getListReadDiscussionByBuyer');
                $router->post('list/product', 'DiscussionController@getListDiscussionByProduct');
                $router->get('detail/{id}', 'DiscussionController@getDiscussionByMasterId');
                $router->get('count/unread', 'DiscussionController@countUnreadDiscussionBuyer');
            });
        });
        $router->group(['prefix' => 'command'], static function () use ($router) {
            $router->group(['prefix' => 'address'], static function () use ($router) {
                $router->group(['middleware' => 'auth'], static function () use ($router) {
                    $router->post('add', 'CustomerController@createCustomerAddress');
                    $router->post('update/{id}', 'CustomerController@updateCustomerAddress');
                    $router->post('default/{id}', 'CustomerController@setDefaultCustomerAddress');
                    $router->delete('delete/{id}', 'CustomerController@deleteCustomerAddress');
                });
            });

            $router->group(['prefix' => 'profile'], static function () use ($router) {
                $router->group(['middleware' => 'auth'], static function () use ($router) {
                    $router->post('update', 'CustomerController@updateCustomerProfile');
                });
            });

            $router->group(['prefix' => 'order', 'middleware' => 'auth'], static function () use ($router) {
                $router->post('checkout', 'TransactionController@checkout');
            });

            $router->group(['prefix' => 'cart', 'middleware' => 'auth'], static function () use ($router) {
                $router->post('add', 'CartController@add');
                $router->delete('delete/{cart_detail_id}/{cart_id}', 'CartController@destroy');
                $router->post('qty/update/{cart_detail_id}/{cart_id}', 'CartController@qtyUpdate');
                $router->delete('all/delete/{related_id}[/{buyer_id}]', 'CartController@deleteAllCart');
            });

            $router->group(['prefix' => 'notification', 'middleware' => 'auth'], static function () use ($router) {
                $router->post('/read/{id}/{rlc_id}', 'NotificationController@buyerReadNotification');
                $router->delete('/delete/{id}/{rlc_id}', 'NotificationController@buyerDeleteNotification');
            });

            $router->group(['prefix' => 'review', 'middleware' => 'auth'], static function () use ($router) {
                $router->post('add', 'ReviewController@addReview');
            });

            $router->group(['prefix' => 'wishlist', 'middleware' => 'auth'], static function () use ($router) {
                $router->post('add/remove', 'WishlistController@addOrRemoveWishlist');
            });

            $router->group(['prefix' => 'testdrive', 'middleware' => 'auth'], static function () use ($router) {
                $router->post('booking/{id}', 'TestDriveController@booking');
                $router->post('booking/{id}/cancel', 'TestDriveController@cancelBooking');
            });

            $router->group(['prefix' => 'discussion', 'middleware' => 'auth'], static function () use ($router) {
                $router->post('create/master', 'DiscussionController@createDiscussionMaster');
                $router->post('create/response', 'DiscussionController@createDiscussionResponseByBuyer');
                $router->post('read/{id}', 'DiscussionController@buyerReadDiscussion');
            });
        });
    });

    $router->group(['prefix' => 'agent'], static function () use ($router) {
        $router->group(['middleware' => 'auth'], function () use ($router) {
            $router->group(['prefix' => 'command', 'middleware' => 'auth'], static function () use ($router) {
                $router->group(['prefix' => 'merchant'], static function () use ($router) {
                    $router->post('atur-profile', 'MerchantController@aturTokoAgent');
                    $router->post('atur-margin', 'MerchantAgentController@setMarginDefault');
                });
                $router->group(['prefix' => 'kudo'], static function () use ($router) {
                    $router->post('payment', 'KudoController@payment');
                });
            });

            $router->group(['prefix' => 'query'], static function () use ($router) {
                $router->group(['prefix' => 'merchant'], static function () use ($router) {
                    $router->get('menu', 'MerchantAgentController@getMenu');
                    $router->get('menu/{agent_id}', 'MerchantAgentController@getDetailMenu');
                });

                $router->group(['prefix' => 'kudo'], static function () use ($router) {
                    $router->get('product-categories', 'KudoController@getProductCategory');
                    $router->get('product-groups/{category_id}', 'KudoController@getProductGroupByCategoryId');
                    $router->get('products/{group_id}', 'KudoController@getProductsByGroupId');
                    $router->get('transaction/user-invoice', 'KudoController@getUserInvoices');
                    $router->post('origin-inquiry', 'KudoController@inquiryKudo');
                    $router->post('invoice/create', 'KudoController@createInvoice');
                });
            });
        });
    });

    $router->group(['prefix' => 'report', 'middleware' => 'auth'], static function () use ($router) {
        $router->get('reason', 'ReportController@getMasterData');
        $router->post('create', 'ReportController@createReport');
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
        $router->get('/', 'SettingProfileController@index');
        $router->post('change-password', 'SettingProfileController@changePassword');
        $router->post('logout', 'SettingProfileController@logout');
    });

    $router->group(['prefix' => 'rajaongkir'], static function () use ($router) {
        $router->get('province', 'RajaOngkirController@getProvince');
        $router->get('district', 'RajaOngkirController@getDistrict');
        $router->post('ongkir', 'RajaOngkirController@ongkir');
        $router->get('couriers', 'RajaOngkirController@couriers');
        $router->post('track', 'RajaOngkirController@trackOrder');
    });

    $router->group(['prefix' => 'order'], static function () use ($router) {
        $router->group(['middleware' => 'auth'], static function () use ($router) {
            $router->post('/{id}/request-cancel', 'TransactionController@requestCancelOrder');
        });
        $router->post('/{id}/cancel', 'TransactionController@cancelOrder');
        $router->post('/{id}/finish', 'TransactionController@finishOrder');
        $router->post('/{id}/refund-ongkir', 'TransactionController@refundOngkir');
    });

    $router->group(['prefix' => 'merchant'], static function () use ($router) {
        $router->get('/list', 'MerchantController@requestMerchantList');
    });

    $router->group(['prefix' => 'iconcash'], static function () use ($router) {
        $router->group(['middleware' => 'auth'], static function () use ($router) {
            $router->post('command/register_customer', 'IconcashController@activation');
            $router->get('command/otp', 'IconcashController@requestOTP');
            $router->post('query/otp/validate', 'IconcashController@validateOTP');
            $router->post('auth/login', 'IconcashController@login');
            $router->get('auth/logout', 'IconcashController@logout');
            $router->post('auth/changepin', 'IconcashController@changePin');
            $router->post('auth/forgotpin', 'IconcashController@forgotPin');
            $router->get('query/balance/customer', 'IconcashController@getCustomerAllBalance');
            $router->post('command/withdrawal/inquiry', 'IconcashController@withdrawalInquiry');
            $router->post('command/withdrawal', 'IconcashController@withdrawal');
            $router->get('query/ref/bank', 'IconcashController@getRefBank');
            $router->post('command/customerbank', 'IconcashController@addCustomerBank');
            $router->get('query/customerbank/search', 'IconcashController@searchCustomerBank');
            $router->get('query/customerbank/{id}', 'IconcashController@getCustomerBankById');
            $router->delete('command/customerbank/{id}', 'IconcashController@deleteCustomerBank');
            $router->put('command/customerbank/{id}', 'IconcashController@updateCustomerBank');
            $router->get('hash-salt/generator/{pin}', 'IconcashController@hash_salt_sha256');
            $router->get('history/saldo-pendapatan', 'IconcashController@historySaldoPendapatan');

            $router->group(['prefix' => 'topup', 'middleware' => 'auth'], static function () use ($router) {
                $router->post('command/topup-confirm', 'IconcashController@topupConfirm');
                $router->post('command/topup-inquiry', 'IconcashController@topupInquiry');
            });
        });
    });

    // $router->group(['middleware' => 'auth'], static function () use ($router) {
    $router->post('aggregator/pln/inquiry', 'ManualTransferController@create');
    $router->get('gettoken', 'ManualTransferController@getToken');
    // });

    $router->group(['prefix' => 'banner'], static function () use ($router) {
        $router->get('all', 'BannerController@getAllBanner');
        $router->get('type/{type}', 'BannerController@getBannerByType');
    });

    $router->group(['prefix' => 'version'], static function () use ($router) {
        $router->post('status', 'VersionController@getVersionStatus');
    });

    $router->group(['prefix' => 'voucher'], static function () use ($router) {
        $router->post('retry/{order_id}', 'TransactionController@retryVoucher');
        $router->post('email/{order_id}', 'TransactionController@resendEmailVoucher');
    });
});
