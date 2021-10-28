<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIconcashCredentialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('iconcash_credentials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('phone');
            $table->string('key');
            $table->longText('token')->nullable();
            $table->string('status');
            $table->string('iconcash_username')->nullable();
            $table->string('iconcash_session_id')->nullable();
            $table->string('iconcash_customer_id')->nullable();
            $table->string('iconcash_customer_name')->nullable();
            $table->timestamps();
            $table->softDeletes();

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
        Schema::dropIfExists('iconcash_credentials');
    }
}
