<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Seeder;

class DistrictSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public $data = ([
        ['name' => 'Senen', 'city_id' => '141', 'created_by' => 'manual input'],
        ['name' => 'Tanah Abang', 'city_id' => '141', 'created_by' => 'manual input'],
        ['name' => 'Sawah Besar', 'city_id' => '141', 'created_by' => 'manual input'],
        ['name' => 'Kebon Jeruk', 'city_id' => '142', 'created_by' => 'manual input'],
        ['name' => 'Cengkareng', 'city_id' => '142', 'created_by' => 'manual input'],
        ['name' => 'Grogol Petamburan', 'city_id' => '142', 'created_by' => 'manual input'],
        ['name' => 'Mampang Prapatan', 'city_id' => '145', 'created_by' => 'manual input'],
        ['name' => 'Pancoran', 'city_id' => '145', 'created_by' => 'manual input'],
        ['name' => 'Tebet', 'city_id' => '145', 'created_by' => 'manual input'],
    ]);
    
    public function run()
    {
        foreach ($this->data as $data) {
            District::updateOrCreate(['name' => $data['name']], $data);
        }
    }
}
