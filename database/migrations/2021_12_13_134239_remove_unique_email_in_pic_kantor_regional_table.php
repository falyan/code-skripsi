<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUniqueEmailInPicKantorRegionalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pic_kantor_regional', function (Blueprint $table) {
            $table->dropUnique('pic_kantor_regional_email_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pic_kantor_regional', function (Blueprint $table) {
            $table->unique('email');
        });
    }
}
