<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVoucherUbahDayaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('master_ubah_daya', function (Blueprint $table) {
            $table->id();
            $table->string('event_name');
            $table->date('event_start_date');
            $table->date('event_end_date');
            $table->integer('quota');
            $table->string('type');
            $table->float('status')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('pregenerate_ubah_daya', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_ubah_daya_id');
            $table->string('kode');
            $table->float('status')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('log_ubah_daya', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('ubah_daya_id');
            $table->unsignedBigInteger('pregenerate_ubah_daya_id')->nullable();
            $table->string('customer_email');
            $table->string('event_name');
            $table->date('event_start_date');
            $table->date('event_end_date');
            $table->date('usage_date')->nullable();
            $table->float('status')->default(1);
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
        Schema::dropIfExists('master_ubah_daya');
        Schema::dropIfExists('pregenerate_ubah_daya');
        Schema::dropIfExists('log_ubah_daya');
    }
}
