<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('merchant_id');
            $table->string('name');
            $table->float('price');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('etalase_id');
            $table->string('condition');
            $table->string('weight');
            $table->longText('description');
            $table->boolean('is_shipping_insurance');
            $table->string('shipping_service');

            $table->string('created_by')->nullable();
            $table->string('updated_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('merchant_id')->references('id')->on('merchant')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('master_data')->onDelete('cascade');
            $table->foreign('etalase_id')->references('id')->on('etalase')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
