<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductEvSubsidy extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_ev_subsidy', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->unsignedBigInteger('product_id');
            $table->float('subsidy_amount', 8, 2);
            $table->string('subsidy_type', 50)->nullable();
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_ev_subsidy', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('order_id');
            $table->string('customer_nik', 100);
            $table->string('customer_id_pel', 100);
            $table->string('umkm_url')->nullable();
            $table->string('kur_url')->nullable();
            $table->string('bpum_url')->nullable();
            $table->float('status_approval')->nullable();
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // merchant update
        Schema::table('merchant', function (Blueprint $table) {
            $table->boolean('is_subsidy_ev')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_ev_subsidy');
        Schema::dropIfExists('customer_ev_subsidy');
        Schema::table('merchant', function (Blueprint $table) {
            $table->dropColumn('is_subsidy_ev');
        });
    }
}
