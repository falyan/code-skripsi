<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnIsReadToProductDiscussionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_discussion_master', function (Blueprint $table) {
            $table->boolean('is_read_merchant')->default(false)->nullable();
        });

        Schema::table('product_discussion_response', function (Blueprint $table) {
            $table->boolean('is_read_customer')->default(false)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_discussion', function (Blueprint $table) {
            //
        });
    }
}
