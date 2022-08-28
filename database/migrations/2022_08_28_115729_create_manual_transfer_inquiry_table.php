<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateManualTransferInquiryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manual_transfer_inquiry', function (Blueprint $table) {
            $table->id();
            $table->string('idtrx');
            $table->string('kodebank');
            $table->string('idpel');
            $table->string('produk');
            $table->string('caref');
            $table->timestamp('date_expired');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('manual_transfer_inquiry');
    }
}
