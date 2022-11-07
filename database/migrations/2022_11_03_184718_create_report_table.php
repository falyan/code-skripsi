<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('report', function (Blueprint $table) {
            $table->id();
            $table->string('report_type');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('review_id')->nullable();
            $table->unsignedBigInteger('product_discussion_master_id')->nullable();
            $table->unsignedBigInteger('product_discussion_response_id')->nullable();
            $table->unsignedBigInteger('reported_by')->nullable();
            $table->unsignedBigInteger('reported_user_id')->nullable();
            $table->string('reason')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')->references('id')->on('product')->onDelete('cascade');
            $table->foreign('review_id')->references('id')->on('review')->onDelete('cascade');
            $table->foreign('product_discussion_master_id')->references('id')->on('product_discussion_master')->onDelete('cascade');
            $table->foreign('product_discussion_response_id')->references('id')->on('product_discussion_response')->onDelete('cascade');
            $table->foreign('reported_by')->references('id')->on('customer')->onDelete('cascade');
            $table->foreign('reported_user_id')->references('id')->on('customer')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('report');
    }
}
