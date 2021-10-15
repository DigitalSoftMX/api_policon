<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('birthdate')->nullable();
            $table->char('sex')->nullable();
            $table->string('address')->nullable();
            $table->double('current_balance')->default(0);
            $table->double('shared_balance')->default(0);
            $table->double('points')->default(0);
            $table->string('ids')->nullable();
            $table->integer('winner')->default(0);
            $table->integer('active')->default(1);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')
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
        Schema::dropIfExists('clients');
    }
}
