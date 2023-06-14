<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePromoMasterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_master', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->string('promo_value_type'); //percentage & fixed
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->string('event_type'); //ongkir & flash sale
            $table->integer('value_1')->nullable();
            $table->integer('value_2')->nullable();
            $table->integer('usage_value')->nullable();
            $table->integer('min_order_value')->nullable();
            $table->integer('max_value')->nullable();
            $table->integer('max_discount_value')->nullable();
            $table->integer('max_value_merchant')->nullable();
            $table->integer('customer_limit_count')->nullable();
            $table->integer('status')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('promo_merchant', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('promo_master_id');
            $table->unsignedBigInteger('merchant_id');
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->integer('usage_value')->nullable();
            $table->integer('max_value')->nullable();
            $table->integer('status')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('promo_master_id')->references('id')->on('promo_master')->onDelete('cascade');
            $table->foreign('merchant_id')->references('id')->on('merchant')->onDelete('cascade');
        });

        Schema::create('promo_value', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('promo_master_id');
            $table->integer('min_value');
            $table->integer('max_value')->nullable();
            $table->integer('max_discount_value')->nullable();
            $table->string('operator')->nullable();
            $table->integer('status')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('promo_master_id')->references('id')->on('promo_master')->onDelete('cascade');
        });

        Schema::create('promo_region', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('promo_master_id');
            $table->string('value_type');
            $table->json('province_ids');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('promo_master_id')->references('id')->on('promo_master')->onDelete('cascade');
        });

        Schema::create('promo_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('promo_master_id');
            $table->unsignedBigInteger('promo_merchant_id');
            $table->enum('type', ['add', 'sub']);
            $table->enum('type_usage', ['master', 'merchant'])->default('merchant');
            $table->string('value');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('order')->onDelete('cascade');
            $table->foreign('promo_master_id')->references('id')->on('promo_master')->onDelete('cascade');
            $table->foreign('promo_merchant_id')->references('id')->on('promo_merchant')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promo_master');
        Schema::dropIfExists('promo_merchant');
        Schema::dropIfExists('promo_value');
        Schema::dropIfExists('promo_region');
        Schema::dropIfExists('promo_log');
    }
}
