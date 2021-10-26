<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableMasterData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('master_data', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type');
            $table->string('key')->nullable();
            $table->string('value_type');
            $table->string('value');
            $table->bigInteger('parent_id')->nullable();
            $table->string('reference_third_party_id')->nullable();
            $table->string('reference_third_party_token')->nullable();
            $table->string('photo_url')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('master_data')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('master_data');
    }
}
