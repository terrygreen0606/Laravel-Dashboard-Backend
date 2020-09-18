<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVesselAisPositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vessel_ais_positions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('vessel_id');
            $table->unsignedInteger('mmsi');
            $table->unsignedInteger('lat');
            $table->unsignedInteger('lon');
            $table->unsignedInteger('speed');
            $table->unsignedInteger('heading');
            $table->unsignedInteger('course');
            $table->unsignedInteger('status');
            $table->dateTime('timestamp');
            $table->string('dsrc');
            $table->string('shipname');
            $table->unsignedInteger('shiptype');
            $table->unsignedInteger('imo');
            $table->string('callsign');
            $table->string('flag');
            $table->string('current_port');
            $table->string('last_port');
            $table->dateTime('last_port_time');
            $table->string('destination');
            $table->string('length');
            $table->string('width');
            $table->unsignedInteger('draught');
            $table->unsignedInteger('grt');
            $table->unsignedInteger('dwt');
            $table->unsignedInteger('year_built');
            $table->unsignedInteger('port_id');
            $table->string('port_unlocode');
            $table->unsignedInteger('last_port_id');
            $table->string('last_port_unlocode');
            $table->dateTime('eta');
            $table->dateTime('eta_calc');
            $table->unsignedInteger('next_port_id');
            $table->string('next_port_unlocode');
            $table->string('next_port_name');
            $table->string('next_port_country');
            $table->string('type_name');
            $table->string('ais_type_summary');
            $table->unsignedInteger('ship_id');
            $table->unsignedInteger('wind_angle');
            $table->unsignedInteger('wind_speed');
            $table->unsignedInteger('wind_temp');
            $table->string('source');
            $table->string('msgtype');
            $table->string('zone_id');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->foreign('vessel_id', 'ais_positions_vessel_id')
                ->references('id')->on('vessels')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // $table->foreign('zone_id', 'vessels_zone_id')
            // ->references('id')->on('zones')
            // ->onDelete('set null')
            // ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vessel_ais_positions');
    }
}
