<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerDiscountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_discount', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('customer_reference_id');
            $table->text('description')->nullable();
            $table->float('amount')->default(0);
            $table->float('used_amount')->default(0);
            $table->date('expired_date')->nullable();
            $table->boolean('id_used')->nullable();
            $table->string('no_reference')->nullable();
            $table->smallInteger('status')->default(1);
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
        Schema::dropIfExists('customer_discount');
    }
}
