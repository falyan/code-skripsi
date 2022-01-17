<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldMainVariantAndStatusToVariantValueProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('variant_value_product', function (Blueprint $table) {
            $table->boolean('main_variant')->default(false)->nullable();
            $table->smallInteger('status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('variant_value_product', function (Blueprint $table) {
            $table->dropColumn(['main_variant', 'status']);
        });
    }
}
