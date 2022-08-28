<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldInPaymentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment', function (Blueprint $table) {
            $table->string('ref_mutasi_rekening')->nullable();
            $table->string('uniq_code', 3)->nullable();
            $table->enum('status_verification', ['unpaid', 'waiting_verification', 'paid', 'canceled', 'refund', 'expired'])->default('unpaid')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment', function (Blueprint $table) {
            $table->dropColumn(['ref_mutasi_rekening', 'uniq_code', 'status_verification']);
        });
    }
}
