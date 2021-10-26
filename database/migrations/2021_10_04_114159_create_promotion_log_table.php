<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePromotionLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promotion_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('promotion_id');
            $table->string('trx_no');
            $table->dateTime('usage_date');
            $table->integer('remaining_usage')->nullable();
            $table->smallInteger('small')->nullable();
            $table->timestamps();

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
        Schema::dropIfExists('promotion_log');
    }
}
