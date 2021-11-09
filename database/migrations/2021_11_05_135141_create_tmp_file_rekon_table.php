<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTmpFileRekonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tmp_file_rekon', function (Blueprint $table) {
            $table->id();
            $table->string('batch_no')->index()->nullable();
            $table->date('tanggal')->nullable();
            $table->string('nama_file')->nullable();
            $table->dateTime('upload_date')->nullable();
            $table->integer('jumlah_data')->nullable();
            $table->float('jumlah_rupiah')->nullable();
            $table->smallInteger('status')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tmp_file_rekon');
    }
}
