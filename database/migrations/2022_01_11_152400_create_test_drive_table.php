<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestDriveTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('test_drive', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('merchant_id');
            $table->string('title');
            $table->string('area_name');
            $table->longText('address');
            $table->bigInteger('city_id');
            $table->string('latitude');
            $table->string('longitude');
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('max_daily_quota');
            $table->string('pic_name');
            $table->string('pic_phone');
            $table->string('pic_email');
            $table->smallInteger('status');
            $table->dateTime('calcelation_date')->nullable();
            $table->string('cancelation_reason')->nullable();
            $table->string('created_by');
            $table->string('updated_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('merchant_id')->references('id')->on('merchant')->onDelete('cascade');
            $table->foreign('city_id')->references('id')->on('city')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('test_drive');
    }
}
