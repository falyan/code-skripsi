<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentMarginTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agent_margin', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_menu_id');
            $table->unsignedBigInteger('merchant_id');
            $table->float('margin');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('agent_menu_id')->references('id')->on('agent_menu')->onDelete('cascade');
            $table->foreign('merchant_id')->references('id')->on('merchant')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agent_margin');
    }
}
