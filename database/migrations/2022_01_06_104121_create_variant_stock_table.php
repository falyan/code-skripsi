<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVariantStockTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('variant_stock', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('variant_value_product_id');
            $table->integer('amount')->nullable();
            $table->json('description')->nullable();
            $table->smallInteger('status')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('variant_value_product_id')->references('id')->on('variant_value_product')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('variant_stock');
    }
}
