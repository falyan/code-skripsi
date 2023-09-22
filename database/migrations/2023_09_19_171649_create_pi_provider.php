<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePiProvider extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pi_provider', function (Blueprint $table) {
            $table->id();
            $table->string('provider_name');
            $table->string('provider_code');
            $table->string('image_url')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pi_provider_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pi_provider_id')->constrained('pi_provider')->onDelete('cascade');
            $table->integer('tenor')->nullable();
            $table->double('mdr_percentage')->nullable();
            $table->integer('fee_provider')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pi_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customer')->onDelete('cascade');
            $table->foreignId('pi_provider_id')->constrained('pi_provider')->onDelete('cascade');
            $table->foreignId('order_id')->constrained('order')->onDelete('cascade');
            $table->integer('month_tenor');
            $table->integer('fee_tenor');
            $table->integer('installment_tenor');
            $table->timestamps();
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
        Schema::dropIfExists('pi_provider_detail');
    }
}
