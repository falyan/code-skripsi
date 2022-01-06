<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVariantValueProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('variant_value_product', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('variant_value_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('description')->nullable();
            $table->float('price')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('variant_value_id')->references('id')->on('variant_value')->onDelete('cascade');
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
        Schema::dropIfExists('variant_value_product');
    }
}
