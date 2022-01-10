<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKantorRegionalPicTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kantor_regional_pic', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kantor_regional_id');
            $table->unsignedBigInteger('pic_kantor_regional_id');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('kantor_regional_id')->references('id')->on('kantor_regional')->onDelete('cascade');
            $table->foreign('pic_kantor_regional_id')->references('id')->on('pic_kantor_regional')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kantor_regional_pic');
    }
}
