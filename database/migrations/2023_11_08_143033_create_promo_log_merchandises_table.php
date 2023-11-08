<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePromoLogMerchandisesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_log_merchandises', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('order_id');
            $table->string('pln_mobile_customer_id', 50)->nullable();
            $table->string('event_name', 50)->nullable();
            $table->integer('mdr_value')->nullable();
            $table->integer('value')->nullable();
            $table->string('reward_id', 50)->nullable();
            $table->integer('value_2')->nullable();
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('customer');
            $table->foreign('order_id')->references('id')->on('order');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promo_log_merchandises');
    }
}
