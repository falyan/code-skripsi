<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderComplaintsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('order')->onDelete('cascade');
            $table->string('complaint');
            $table->string('description')->nullable();
            $table->string('status')->nullable();
            $table->string('image')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('log_ubah_daya', function (Blueprint $table) {
            $table->string('nik')->nullable();
            $table->timestamp('with_nik_claim_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_complaints');
        Schema::table('log_ubah_daya', function (Blueprint $table) {
            $table->dropColumn('nik');
            $table->dropColumn('with_nik_claim_at');
        });
    }
}
