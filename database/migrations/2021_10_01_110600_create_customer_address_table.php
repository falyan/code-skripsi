<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerAddressTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_address', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('customer_id');
            $table->longText('address');
            $table->unsignedBigInteger('city_id');
            $table->unsignedBigInteger('province_id');
            $table->string('postal_code');
            $table->string('longitude');
            $table->string('latitude');
            $table->string('receiver_name');
            $table->string('receiver_phone');
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customer')->onDelete('cascade');
            $table->foreign('city_id')->references('id')->on('city')->onDelete('cascade');
            $table->foreign('province_id')->references('id')->on('province')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_address');
    }
}
