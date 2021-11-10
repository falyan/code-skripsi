<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryDiscountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('delivery_discount', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('discount_code')->nullable();
            $table->float('discount_amount')->nullable();
            $table->float('minimum_transaction_amount')->nullable()->default(0);
            $table->text('expedition_list')->nullable();
            $table->string('photo_url')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('delivery_discount');
    }
}
