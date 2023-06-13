<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agent_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->string('customer_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('trx_no');
            $table->string('product_id')->nullable();
            $table->string('product_name')->nullable();
            $table->string('product_value')->nullable();
            $table->string('product_key')->nullable();
            $table->timestamp('order_date');
            $table->float('amount');
            $table->float('margin');
            $table->float('total_fee');
            $table->float('total_amount');
            $table->json('order_detail')->nullable();
            // $table->bool('has_receipt')->default(false);
            $table->string('invoice_no')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('agent_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_order_id');
            $table->string('payment_id')->nullable();
            $table->string('payment_method');
            $table->string('trx_reference')->nullable();
            $table->json('payment_detail')->nullable();
            $table->float('amount')->nullable();
            $table->float('total_fee')->nullable();
            $table->float('total_amount')->nullable();
            $table->string('payment_scenario')->nullable();
            $table->string('source_account_id')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('agent_order_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_order_id');
            $table->string('status_code');
            $table->string('status_name');
            $table->string('status_note')->nullable();
            $table->integer('status')->default(1);
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
        Schema::dropIfExists('agent_orders');
        Schema::dropIfExists('agent_payments');
        Schema::dropIfExists('agent_order_progress');
    }
}
