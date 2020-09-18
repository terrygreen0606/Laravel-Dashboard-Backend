<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVesselAisDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vessel_ais_details', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('vessel_id');
            $table->unsignedInteger('mmsi');
            $table->unsignedInteger('imo');
            $table->string('name');
            $table->string('place_of_build');
            $table->unsignedInteger('build');
            $table->string('breadth_extreme');
            $table->unsignedInteger('summer_dwt');
            $table->unsignedInteger('displacement_summer');
            $table->string('callsign');
            $table->string('flag');
            $table->string('draught');
            $table->string('length_overall');
            $table->string('fuel_consumption');
            $table->string('speed_max');
            $table->string('speed_service');
            $table->unsignedInteger('liquid_oil');
            $table->string('owner');
            $table->string('manager');
            $table->string('vessel_type');
            $table->string('manager_owner');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->foreign('vessel_id', 'ais_details_vessel_id')
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
        Schema::dropIfExists('vessel_ais_details');
    }
}
