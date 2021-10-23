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
            $table->integer('type');
            $table->dateTime('issued_date');
            $table->integer('limit_usage');
            $table->integer('counter_usage');
            $table->string('amount_type');
            $table->float('amount');
            $table->float('minimum_transaction_amount');
            $table->float('maximum_promo_amount');
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
