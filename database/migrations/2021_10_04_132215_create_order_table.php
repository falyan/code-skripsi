<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('seller_id')->nullable();
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->string('trx_no')->nullable();
            $table->dateTime('order_date')->nullable();
            $table->float('total_amount')->nullable();
            $table->float('payment_amount')->nullable();
            $table->float('total_weight')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('delivery_method')->nullable();
            $table->string('related_buyer_id')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('seller_id')->references('id')->on('customer')->onDelete('cascade');
            $table->foreign('buyer_id')->references('id')->on('customer')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order');
    }
}
