<?php

namespace Database\Seeders;

use App\Models\Notification;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public $data = ([
        ['customer_id' => 1, 'merchant_id' => null, 'user_bot_id' => null, 'type' => 2, 'title' => 'Menunggu Pembayaran', 'message' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Dicta doloribus nostrum sint ullam assumenda totam harum tempora ea ipsa quod.', 'url_path' => '/v1/buyer/query/transaction/1234/detail/1', 'created_by' => 'system', 'status' => 0, 'related_pln_mobile_customer_id' => '123123'],
        ['customer_id' => 1, 'merchant_id' => null, 'user_bot_id' => null, 'type' => 2, 'title' => 'Menunggu Pembayaran', 'message' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Dicta doloribus nostrum sint ullam assumenda totam harum tempora ea ipsa quod.', 'url_path' => '/v1/buyer/query/transaction/1234/detail/3', 'created_by' => 'system', 'status' => 0, 'related_pln_mobile_customer_id' => '123123'],
        ['customer_id' => 1, 'merchant_id' => null, 'user_bot_id' => null, 'type' => 2, 'title' => 'Menunggu Pembayaran', 'message' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Dicta doloribus nostrum sint ullam assumenda totam harum tempora ea ipsa quod.', 'url_path' => '/v1/buyer/query/transaction/1234/detail/3', 'created_by' => 'system', 'status' => 0, 'related_pln_mobile_customer_id' => '123123'],
        ['customer_id' => null, 'merchant_id' => null, 'user_bot_id' => null, 'type' => 2, 'title' => 'Menunggu Pembayaran', 'message' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Dicta doloribus nostrum sint ullam assumenda totam harum tempora ea ipsa quod.', 'url_path' => '/v1/buyer/query/transaction/1234/detail/4', 'created_by' => 'system', 'status' => 0, 'related_pln_mobile_customer_id' => '1234'],
        ['customer_id' => null, 'merchant_id' => null, 'user_bot_id' => null, 'type' => 2, 'title' => 'Menunggu Pembayaran', 'message' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Dicta doloribus nostrum sint ullam assumenda totam harum tempora ea ipsa quod.', 'url_path' => '/v1/buyer/query/transaction/1234/detail/5', 'created_by' => 'system', 'status' => 0, 'related_pln_mobile_customer_id' => '1234'],
        ['customer_id' => null, 'merchant_id' => null, 'user_bot_id' => null, 'type' => 2, 'title' => 'Menunggu Pembayaran', 'message' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Dicta doloribus nostrum sint ullam assumenda totam harum tempora ea ipsa quod.', 'url_path' => '/v1/buyer/query/transaction/1234/detail/6', 'created_by' => 'system', 'status' => 0, 'related_pln_mobile_customer_id' => '1234'],
        ['customer_id' => null, 'merchant_id' => null, 'user_bot_id' => null, 'type' => 2, 'title' => 'Menunggu Pembayaran', 'message' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Dicta doloribus nostrum sint ullam assumenda totam harum tempora ea ipsa quod.', 'url_path' => '/v1/buyer/query/transaction/1234/detail/7', 'created_by' => 'system', 'status' => 0, 'related_pln_mobile_customer_id' => '1234'],
        ['customer_id' => null, 'merchant_id' => null, 'user_bot_id' => null, 'type' => 2, 'title' => 'Menunggu Pembayaran', 'message' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Dicta doloribus nostrum sint ullam assumenda totam harum tempora ea ipsa quod.', 'url_path' => '/v1/buyer/query/transaction/1234/detail/8', 'created_by' => 'system', 'status' => 0, 'related_pln_mobile_customer_id' => '1234'],
        ['customer_id' => 9, 'merchant_id' => null, 'user_bot_id' => null, 'type' => 2, 'title' => 'Menunggu Pembayaran', 'message' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Dicta doloribus nostrum sint ullam assumenda totam harum tempora ea ipsa quod.', 'url_path' => '/v1/buyer/query/transaction/1234/detail/21', 'created_by' => 'system', 'status' => 0, 'related_pln_mobile_customer_id' => '1234567890'],
        ['customer_id' => 9, 'merchant_id' => null, 'user_bot_id' => null, 'type' => 2, 'title' => 'Menunggu Pembayaran', 'message' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Dicta doloribus nostrum sint ullam assumenda totam harum tempora ea ipsa quod.', 'url_path' => '/v1/buyer/query/transaction/1234/detail/22', 'created_by' => 'system', 'status' => 0, 'related_pln_mobile_customer_id' => '1234567890'],
        ['customer_id' => 9, 'merchant_id' => null, 'user_bot_id' => null, 'type' => 2, 'title' => 'Menunggu Pembayaran', 'message' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Dicta doloribus nostrum sint ullam assumenda totam harum tempora ea ipsa quod.', 'url_path' => '/v1/buyer/query/transaction/1234/detail/23', 'created_by' => 'system', 'status' => 0, 'related_pln_mobile_customer_id' => '1234567890'],
        ['customer_id' => 9, 'merchant_id' => null, 'user_bot_id' => null, 'type' => 2, 'title' => 'Menunggu Pembayaran', 'message' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Dicta doloribus nostrum sint ullam assumenda totam harum tempora ea ipsa quod.', 'url_path' => '/v1/buyer/query/transaction/1234/detail/24', 'created_by' => 'system', 'status' => 0, 'related_pln_mobile_customer_id' => '1234567890'],
        ['customer_id' => 9, 'merchant_id' => null, 'user_bot_id' => null, 'type' => 2, 'title' => 'Menunggu Pembayaran', 'message' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Dicta doloribus nostrum sint ullam assumenda totam harum tempora ea ipsa quod.', 'url_path' => '/v1/buyer/query/transaction/1234/detail/25', 'created_by' => 'system', 'status' => 0, 'related_pln_mobile_customer_id' => '1234567890'],
        ['customer_id' => 9, 'merchant_id' => null, 'user_bot_id' => null, 'type' => 2, 'title' => 'Menunggu Pembayaran', 'message' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Dicta doloribus nostrum sint ullam assumenda totam harum tempora ea ipsa quod.', 'url_path' => '/v1/buyer/query/transaction/1234/detail/26', 'created_by' => 'system', 'status' => 0, 'related_pln_mobile_customer_id' => '1234567890'],
        ['customer_id' => 9, 'merchant_id' => null, 'user_bot_id' => null, 'type' => 2, 'title' => 'Menunggu Pembayaran', 'message' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Dicta doloribus nostrum sint ullam assumenda totam harum tempora ea ipsa quod.', 'url_path' => '/v1/buyer/query/transaction/1234/detail/27', 'created_by' => 'system', 'status' => 0, 'related_pln_mobile_customer_id' => '1234567890'],
        ['customer_id' => 9, 'merchant_id' => null, 'user_bot_id' => null, 'type' => 2, 'title' => 'Menunggu Pembayaran', 'message' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Dicta doloribus nostrum sint ullam assumenda totam harum tempora ea ipsa quod.', 'url_path' => '/v1/buyer/query/transaction/1234/detail/28', 'created_by' => 'system', 'status' => 0, 'related_pln_mobile_customer_id' => '1234567890'],
        ['customer_id' => 9, 'merchant_id' => null, 'user_bot_id' => null, 'type' => 2, 'title' => 'Menunggu Pembayaran', 'message' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Dicta doloribus nostrum sint ullam assumenda totam harum tempora ea ipsa quod.', 'url_path' => '/v1/buyer/query/transaction/1234/detail/29', 'created_by' => 'system', 'status' => 0, 'related_pln_mobile_customer_id' => '1234567890'],
        ['customer_id' => 9, 'merchant_id' => null, 'user_bot_id' => null, 'type' => 2, 'title' => 'Menunggu Pembayaran', 'message' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Dicta doloribus nostrum sint ullam assumenda totam harum tempora ea ipsa quod.', 'url_path' => '/v1/buyer/query/transaction/1234/detail/30', 'created_by' => 'system', 'status' => 0, 'related_pln_mobile_customer_id' => '1234567890'],
        ['customer_id' => 9, 'merchant_id' => null, 'user_bot_id' => null, 'type' => 2, 'title' => 'Menunggu Pembayaran', 'message' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Dicta doloribus nostrum sint ullam assumenda totam harum tempora ea ipsa quod.', 'url_path' => '/v1/buyer/query/transaction/1234/detail/31', 'created_by' => 'system', 'status' => 0, 'related_pln_mobile_customer_id' => '1234567890'],
        ['customer_id' => 9, 'merchant_id' => null, 'user_bot_id' => null, 'type' => 2, 'title' => 'Menunggu Pembayaran', 'message' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Dicta doloribus nostrum sint ullam assumenda totam harum tempora ea ipsa quod.', 'url_path' => '/v1/buyer/query/transaction/1234/detail/32', 'created_by' => 'system', 'status' => 0, 'related_pln_mobile_customer_id' => '1234567890'],
        ['customer_id' => 9, 'merchant_id' => null, 'user_bot_id' => null, 'type' => 2, 'title' => 'Menunggu Pembayaran', 'message' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Dicta doloribus nostrum sint ullam assumenda totam harum tempora ea ipsa quod.', 'url_path' => '/v1/buyer/query/transaction/1234/detail/33', 'created_by' => 'system', 'status' => 0, 'related_pln_mobile_customer_id' => '1234567890'],
        ['customer_id' => 9, 'merchant_id' => null, 'user_bot_id' => null, 'type' => 2, 'title' => 'Menunggu Pembayaran', 'message' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Dicta doloribus nostrum sint ullam assumenda totam harum tempora ea ipsa quod.', 'url_path' => '/v1/buyer/query/transaction/1234/detail/34', 'created_by' => 'system', 'status' => 0, 'related_pln_mobile_customer_id' => '1234567890'],
    ]);

    public function run()
    {
        foreach ($this->data as $data) {
            Notification::updateOrCreate($data);
        }
    }
}
