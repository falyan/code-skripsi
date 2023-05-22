<?php

use App\Models\MasterData;
use App\Models\MasterTiket;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterTiketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('master_tiket', function (Blueprint $table) {
            $table->id();
            $table->string('master_data_key');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('event_address')->nullable();
            $table->date('start_bill_date');
            $table->date('end_bill_date');
            $table->date('usage_date');
            $table->time('start_time_usage')->nullable();
            $table->time('end_time_usage')->nullable();
            $table->float('status');
            $table->json('tnc')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_tiket', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('master_tiket_id');
            $table->unsignedBigInteger('order_id');
            $table->string('number_tiket');
            $table->date('usage_date');
            $table->time('start_time_usage')->nullable();
            $table->time('end_time_usage')->nullable();
            $table->string('event_info')->nullable();
            $table->float('status');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('master_tiket_id')->references('id')->on('master_tiket');
            $table->foreign('order_id')->references('id')->on('order')->onDelete('cascade');
        });

        Schema::table('merchant', function (Blueprint $table) {
            $table->boolean('official_store_tiket')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('merchant', function (Blueprint $table) {
            $table->dropColumn('official_store_ticket');
        });
        Schema::dropIfExists('customer_tiket');
        Schema::dropIfExists('master_tiket');
    }
}
