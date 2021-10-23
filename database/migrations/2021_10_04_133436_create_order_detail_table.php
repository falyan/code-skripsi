<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_detail', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->integer('detail_type')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('promotion_id')->nullable();
            $table->integer('quantity')->nullable();
            $table->float('price')->nullable();
            $table->float('weight')->nullable();
            $table->float('insurance_cost')->nullable();
            $table->float('discount')->nullable();
            $table->float('total_price')->nullable();
            $table->float('total_weight')->nullable();
            $table->float('total_discount')->nullable();
            $table->float('total_insurance_cost')->nullable();
            $table->float('total_amount')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('order_id')->references('id')->on('order')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('product')->onDelete('cascade');
            $table->foreign('promotion_id')->references('id')->on('promotion')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_detail');
    }
}
