<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVesselAisMtPollTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vessel_ais_mt_poll', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('vessel_id');
            $table->unsignedInteger('imo');
            $table->unsignedInteger('satellite');
            $table->string('extended');
            $table->unsignedInteger('interval');
            $table->dateTime('last_update');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            
            $table->foreign('vessel_id', 'ais_mt_poll_vessel_id')
                ->references('id')->on('vessels')
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
        Schema::dropIfExists('vessel_ais_mt_poll');
    }
}
