<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessagingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messaging', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->smallInteger('type')->nullable();
            $table->string('destination_id')->nullable();
            $table->string('message_name')->nullable();
            $table->string('message_title')->nullable();
            $table->string('message_body')->nullable();
            $table->string('message_type')->nullable();
            $table->string('message_schedule')->nullable();
            $table->json('description')->nullable();
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
        Schema::dropIfExists('messaging');
    }
}
