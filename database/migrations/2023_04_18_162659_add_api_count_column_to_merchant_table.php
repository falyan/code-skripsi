<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApiCountColumnToMerchantTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('merchant', function (Blueprint $table) {
            $table->integer('api_count')->default(0);
            $table->boolean('store_umkm')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('merchant', function (Blueprint $table) {
            $table->dropColumn('api_count');
            $table->dropColumn('store_umkm');
        });
    }
}
