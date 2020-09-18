<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNetworkVesselsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('network_vessels', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('network_id');
            $table->unsignedInteger('vessel_id');
            $table->timestamps();

            $table->unique(['network_id', 'vessel_id'], 'network_vessels_network_id_vessel_id_unique');


            $table->foreign('network_id', 'network_vessels_network_id')
                ->references('id')->on('networks')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('vessel_id', 'network_vessels_vessel_id')
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
        Schema::dropIfExists('network_vessels');
    }
}
