<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMerchantOperationalHourTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('merchant_operational_hour', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('merchant_id');
            $table->unsignedBigInteger('master_data_id');
            $table->time('open_time')->nullable();
            $table->time('closed_time')->nullable();
            $table->string('timezone')->nullable();
            $table->timestamps();

            $table->foreign('merchant_id')->references('id')->on('merchant')->onDelete('cascade');
            $table->foreign('master_data_id')->references('id')->on('master_data')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('merchant_operational_hour');
    }
}
