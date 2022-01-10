<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOptionVariantTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('option_variant', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_variant_id');
            $table->string('nama')->nullable();
            $table->timestamps();

            $table->foreign('master_variant_id')->references('id')->on('master_variant')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('option_variants');
    }
}
