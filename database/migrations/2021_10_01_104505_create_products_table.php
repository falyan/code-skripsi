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
            $table->float('strike_price');
            $table->integer('minimum_purchase');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('etalase_id')->nullable();
            $table->string('condition')->nullable();
            $table->string('weight')->nullable();
            $table->longText('description')->nullable();
            $table->boolean('is_shipping_insurance')->nullable();
            $table->string('shipping_service')->nullable();
            $table->boolean('is_featured_product')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
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
