<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMerchantTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('merchant', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('phone_office');
            $table->unsignedBigInteger('corporate_id');
            $table->unsignedBigInteger('industry_type_id')->nullable();
            $table->longText('address');
            $table->longText('description');
            $table->string('slogan');
            $table->unsignedBigInteger('province_id');
            $table->unsignedBigInteger('city_id');
            $table->unsignedBigInteger('district_id');
            $table->string('postal_code');
            $table->string('longitude')->nullable();
            $table->string('latitude')->nullable();
            $table->string('email');
            $table->string('photo_url')->nullable();
            $table->unsignedBigInteger('pic_id');
            $table->string('npwp')->nullable();
            $table->string('npwp_url')->nullable();
            $table->string('nib_url')->nullable();
            $table->smallInteger('status');
            $table->boolean('is_open');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('reference_customer_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('corporate_id')->references('id')->on('corporate')->onDelete('cascade');
            $table->foreign('province_id')->references('id')->on('province')->onDelete('cascade');
            $table->foreign('city_id')->references('id')->on('city')->onDelete('cascade');
            $table->foreign('district_id')->references('id')->on('district')->onDelete('cascade');
            $table->foreign('pic_id')->references('id')->on('pic')->onDelete('cascade');
            $table->foreign('industry_type_id')->references('id')->on('master_data')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('merchant');
    }
}
