<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnInCustomerEvSubsidyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer_ev_subsidy', function (Blueprint $table) {
            $table->string('customer_full_name', 100)->nullable();
            $table->string('customer_father_name', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer_ev_subsidy', function (Blueprint $table) {
            //
        });
    }
}
