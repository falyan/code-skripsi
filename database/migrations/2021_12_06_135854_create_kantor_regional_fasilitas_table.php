<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKantorRegionalFasilitasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kantor_regional_fasilitas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kantor_regional_id');
            $table->unsignedBigInteger('fasilitas_id');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('kantor_regional_id')->references('id')->on('kantor_regional')->onDelete('cascade');
            $table->foreign('fasilitas_id')->references('id')->on('fasilitas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kantor_regional_fasilitas');
    }
}
