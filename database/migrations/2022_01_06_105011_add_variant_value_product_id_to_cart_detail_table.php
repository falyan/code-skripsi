<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVariantValueProductIdToCartDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cart_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('variant_value_product_id')->nullable();

            $table->foreign('variant_value_product_id')->references('id')->on('variant_value_product')->nullOnDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cart_detail', function (Blueprint $table) {
            $table->dropColumn('variant_value_product_id');
        });
    }
}
