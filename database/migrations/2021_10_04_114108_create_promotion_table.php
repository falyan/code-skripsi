<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePromotionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promotion', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('promo_code');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->integer('reset_duration')->nullable();
            $table->dateTime('date_reset')->nullable();
            $table->integer('limit_usage')->nullable();
            $table->integer('counter_usage')->nullable();
            $table->string('amount_type');
            $table->float('amount');
            $table->float('minimum_transaction_amount')->nullable();
            $table->float('maximum_promo_amount')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
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
        Schema::dropIfExists('promotion');
    }
}
