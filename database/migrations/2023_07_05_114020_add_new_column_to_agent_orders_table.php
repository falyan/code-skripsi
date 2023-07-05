<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnToAgentOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('agent_orders', function (Blueprint $table) {
            $table->float('fee_agent')->nullable()->after('margin');
            $table->float('fee_iconpay')->nullable()->after('fee_agent');
        });

        Schema::table('agent_master_data', function (Blueprint $table) {
            $table->integer('fee_iconpay')->default(0)->after('fee');
        });

        Schema::table('agent_payments', function (Blueprint $table) {
            $table->float('fee_agent')->nullable()->after('amount');
            $table->float('fee_iconpay')->nullable()->after('fee_agent');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('agent_orders', function (Blueprint $table) {
            //
        });
    }
}
