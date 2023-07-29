<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationsLogisticsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // province_logistics
        Schema::table('province', function (Blueprint $table) {
            $table->string('shipper_id')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
        });

        // city_logistics
        Schema::table('city', function (Blueprint $table) {
            $table->string('shipper_id')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('postal_code')->nullable();
        });

        // district_logistics
        Schema::table('district', function (Blueprint $table) {
            $table->string('shipper_id')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
        });

        // subdistrict_logistics
        Schema::create('subdistrict', function (Blueprint $table) {
            $table->id();
            $table->string('district_id')->nullable();
            $table->string('shipper_id')->nullable();
            $table->string('name');
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::table('order_delivery', function (Blueprint $table) {
            $table->integer('subdistrict_id')->nullable();
            $table->string('delivery_type')->nullable();
            $table->string('delivery_setting')->nullable();
            $table->json('merchant_data')->nullable();
            $table->boolean('must_use_insurance')->default(false);
        });

        Schema::table('customer_address', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // province_logistics
        Schema::table('province', function (Blueprint $table) {
            $table->dropColumn('shipper_id');
            $table->dropColumn('latitude');
            $table->dropColumn('longitude');
        });

        // city_logistics
        Schema::table('city', function (Blueprint $table) {
            $table->dropColumn('shipper_id');
            $table->dropColumn('latitude');
            $table->dropColumn('longitude');
            $table->dropColumn('postal_code');
        });

        // district_logistics
        Schema::table('district', function (Blueprint $table) {
            $table->dropColumn('shipper_id');
            $table->dropColumn('latitude');
            $table->dropColumn('longitude');
        });

        // subdistrict_logistics
        Schema::dropIfExists('subdistrict');

        Schema::table('order_delivery', function (Blueprint $table) {
            $table->dropColumn('subdistrict_id');
            $table->dropColumn('delivery_type');
            $table->dropColumn('delivery_setting');
            $table->dropColumn('merchant_data');
        });

        Schema::table('customer_address', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
