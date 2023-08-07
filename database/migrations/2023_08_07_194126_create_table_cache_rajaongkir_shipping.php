<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableCacheRajaongkirShipping extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cache_rajaongkir_shipping', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value');
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
        });

        Schema::table('raja_ongkir_setting', function (Blueprint $table) {
            $table->string('type_key')->default('pro');
            $table->string('base_url')->default('https://pro.rajaongkir.com');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cache_rajaongkir_shipping');

        Schema::table('raja_ongkir_setting', function (Blueprint $table) {
            $table->dropColumn('type_key');
            $table->dropColumn('base_url');
        });
    }
}
