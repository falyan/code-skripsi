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
            $table->unsignedBigInteger('merchant_id')->nullable();
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->string('trx_no')->nullable();
            $table->dateTime('order_date')->nullable();
            $table->float('total_amount')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->float('total_weight')->nullable();
            $table->string('delivery_method')->nullable();
            $table->string('related_pln_mobile_customer_id')->nullable();
            $table->string('buyer_phone')->nullable();
            $table->string('buyer_email')->nullable();
            $table->string('no_reference')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('merchant_id')->references('id')->on('merchant')->onDelete('cascade');
            $table->foreign('buyer_id')->references('id')->on('customer')->onDelete('cascade');
            $table->foreign('payment_id')->references('id')->on('payment')->onDelete('cascade');
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
