<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->smallInteger('status')->nullable();
            $table->string('name')->nullable();
            $table->smallInteger('type')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->string('url_path')->nullable();
            $table->dateTime('last_execute')->nullable();
            $table->integer('status_return')->nullable();
            $table->integer('day')->nullable();
            $table->integer('hour')->nullable();
            $table->integer('minute')->nullable();
            $table->integer('counter_fail')->nullable();
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
        Schema::dropIfExists('schedules');
    }
}
