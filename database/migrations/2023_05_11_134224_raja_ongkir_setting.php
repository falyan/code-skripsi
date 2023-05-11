<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RajaOngkirSettingMigratons extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('raja_ongkir_setting', function (Blueprint $table) {
            $table->id();
            $table->string('credential_key');
            $table->string('time_start');
            $table->string('time_end');
            $table->string('status');
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
        Schema::dropIfExists('raja_ongkir_setting');
    }
}
