<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestDriveProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('test_drive_product', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('test_drive_id');
            $table->bigInteger('product_id');
            $table->timestamps();

            $table->foreign('test_drive_id')->references('id')->on('test_drive')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('product')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('test_drive_product');
    }
}
