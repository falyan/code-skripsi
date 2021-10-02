<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCorporateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('corporate', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('area_name');
            $table->string('phone_office');
            $table->longText('address');
            $table->unsignedBigInteger('province_id');
            $table->unsignedBigInteger('city_id');
            $table->string('district');
            $table->string('email');
            $table->string('fax_number')->nullable();
            $table->string('npwp');
            $table->string('npwp_url');
            $table->unsignedBigInteger('pic_id');
            $table->smallInteger('status');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('province_id')->references('id')->on('province')->onDelete('cascade');
            $table->foreign('city_id')->references('id')->on('city')->onDelete('cascade');
            $table->foreign('pic_id')->references('id')->on('pic')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('corporate');
    }
}
