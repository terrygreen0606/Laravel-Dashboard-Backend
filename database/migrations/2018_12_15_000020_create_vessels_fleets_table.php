<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVesselsFleetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vessels_fleets', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('vessel_id');
            $table->unsignedInteger('fleet_id');
            $table->timestamps();

            $table->unique(['vessel_id', 'fleet_id'], 'vessels_fleets_vessel_id_fleet_id_unique');


            $table->foreign('vessel_id', 'vessels_fleets_vessel_id')
                ->references('id')->on('vessels')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('fleet_id', 'vessels_fleets_fleet_id')
                ->references('id')->on('fleets')
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
        Schema::dropIfExists('vessels_fleets');
    }
}
