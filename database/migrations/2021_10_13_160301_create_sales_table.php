<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale');
            $table->double('liters');
            $table->double('payment');
            $table->unsignedBigInteger('station_id');
            $table->unsignedBigInteger('client_id');
            $table->timestamps();

            $table->foreign('station_id')->references('id')->on('station')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('client_id')->references('id')->on('clients')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales');
    }
}
