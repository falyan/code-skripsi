<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnInBannerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('iconcash_inquiry', function (Blueprint $table) {
            $table->string('client_ref')->nullable();
            $table->string('iconcash_order_id')->nullable();
            $table->json('res_json')->nullable();
            $table->json('confirm_res_json')->nullable();
            $table->boolean('confirm_status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('iconcash_inquiry', function (Blueprint $table) {
            $table->dropColumn('client_ref');
            $table->dropColumn('iconcash_order_id');
            $table->dropColumn('res_json');
            $table->dropColumn('confirm_res_json');
            $table->dropColumn('confirm_status');
        });
    }
}
