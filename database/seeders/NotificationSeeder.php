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
    ]);

    public function run()
    {
        foreach ($this->data as $data) {
            Notification::updateOrCreate($data);
        }
    }
}
