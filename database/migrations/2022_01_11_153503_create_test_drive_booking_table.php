<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestDriveBookingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('test_drive_booking', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('test_drive_id');
            $table->bigInteger('customer_id');
            $table->date('visit_date');
            $table->string('pic_name');
            $table->string('pic_phone');
            $table->string('pic_email');
            $table->smallInteger('total_passanger');
            $table->string('booking_code');
            $table->smallInteger('status');
            $table->timestamps();

            $table->foreign('test_drive_id')->references('id')->on('test_drive')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customer')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('test_drive_booking');
    }
}
