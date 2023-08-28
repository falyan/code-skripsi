<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderComplaintsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('log_ubah_daya', function (Blueprint $table) {
            $table->string('nik')->nullable();
            $table->timestamp('with_nik_claim_at')->nullable();
        });

        Schema::table('product', function (Blueprint $table) {
            $table->boolean('insentif_ubah_daya')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_ubah_daya', function (Blueprint $table) {
            $table->dropColumn('nik');
            $table->dropColumn('with_nik_claim_at');
        });

        Schema::table('product', function (Blueprint $table) {
            $table->dropColumn('insentif_ubah_daya');
        });
    }
}
