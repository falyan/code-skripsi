<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCartDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cart_detail', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->integer('quantity')->nullable();
            $table->unsignedBigInteger('related_merchant_id')->nullable();
            $table->timestamps();

            $table->foreign('cart_id')->references('id')->on('cart')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('product')->onDelete('cascade');
            $table->foreign('related_merchant_id')->references('id')->on('merchant')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cart_detail');
    }
}
