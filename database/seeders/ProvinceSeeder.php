<?php

namespace Database\Seeders;

use App\Http\Services\Manager\RajaOngkirManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $provinces = RajaOngkirManager::getProvinces();
        // Log::info(json_encode($provinces));
        // DB::table('province')->insert([

        // ]);
    }
}
