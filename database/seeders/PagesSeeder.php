<?php

namespace Database\Seeders;

use App\Models\Pages;
use Illuminate\Database\Seeder;

class PagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public $data = ([
        ['page_type' => 'term_condition', 'title' => 'Term & Condition'],
        ['page_type' => 'contact_us', 'title' => 'Contact Us'],
        ['page_type' => 'about_us', 'title' => 'About Us'],
        ['page_type' => 'privacy_policy', 'title' => 'Privacy & Policy']
    ]);

    public function run()
    {
        foreach ($this->data as $data) {
            Pages::updateOrCreate([
                'page_type' => $data['page_type'],
                'body' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Dicta doloribus nostrum sint ullam assumenda totam harum tempora ea ipsa quod. Nesciunt aperiam quisquam magni. Eos rem sapiente voluptas libero animi?'
            ], $data);
        }
    }
}
