<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('username');
            $table->string('password');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('type');
            $table->unsignedBigInteger('merchant_id')->nullable();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->smallInteger('status');
            $table->string('related_pln_mobile_customer_id')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('already_registered_merchant')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('role_id')->references('id')->on('role')->onDelete('cascade');
            $table->foreign('merchant_id')->references('id')->on('merchant')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer');
    }
}
