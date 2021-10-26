<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEtalaseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('etalase', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('merchant_id');
            $table->string('name');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestampsTz();

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
        Schema::dropIfExists('etalase');
    }
}
