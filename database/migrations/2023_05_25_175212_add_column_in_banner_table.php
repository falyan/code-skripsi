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
        Schema::table('banner', function (Blueprint $table) {
            $table->string('link_url')->nullable();
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->string('button_label')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('banner', function (Blueprint $table) {
            //
        });
    }
}
