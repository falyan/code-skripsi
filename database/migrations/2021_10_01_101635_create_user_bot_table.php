<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserBotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_bot', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('username');
            $table->string('password');
            $table->string('email');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('corporate_id');
            $table->smallInteger('status');
            $table->string('created_by')->nullable();
            $table->string('updated_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('role_id')->references('id')->on('role')->onDelete('cascade');
            $table->foreign('corporate_id')->references('id')->on('corporate')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_bot');
    }
}
