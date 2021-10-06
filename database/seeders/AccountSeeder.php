<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'full_name' => 'alexander buyer',
            'username' => 'buyertest',
            'password' => Hash::make('123456'),
            'email' => 'buyertest@email.com',
            'phone' => '08123123723',
            'type' => 'buyer',
            'status' => 1,
            'created_by' => 'Manual Input'
        ]);
    }
}
