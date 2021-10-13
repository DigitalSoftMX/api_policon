<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesQrsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_qrs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('sale');
            $table->integer('gasoline');
            $table->double('liters');
            $table->integer('points');
            $table->double('payment');
            $table->unsignedBigInteger('station_id');
            $table->unsignedBigInteger('client_id');
            $table->timestamps();

            $table->foreign('station_id')->references('id')->on('station')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('client_id')->references('id')->on('clients')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales_qrs');
    }
}
