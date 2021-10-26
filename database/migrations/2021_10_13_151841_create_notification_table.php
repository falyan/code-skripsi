<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('merchant_id')->nullable();
            $table->unsignedBigInteger('user_bot_id')->nullable();
            $table->smallInteger('type')->nullable();
            $table->string('title')->nullable();
            $table->string('message')->nullable();
            $table->string('url_path')->nullable();
            $table->smallInteger('status')->nullable();
            $table->string('related_pln_mobile_customer_id')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('merchant_id')->references('id')->on('merchant')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customer')->onDelete('cascade');
            $table->foreign('user_bot_id')->references('id')->on('user_bot')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notification');
    }
}
