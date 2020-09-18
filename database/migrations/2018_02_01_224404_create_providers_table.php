<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ais_providers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('api_name')->nullable();
            $table->string('api_vessels_link')->nullable();
            $table->boolean('api_fleets_enabled')->default(0);
            $table->string('api_fleets_link')->nullable();
            $table->string('api_vessels_in_fleet_link')->nullable();
            $table->string('api_fields_map', 4096)->nullable();//json object
            $table->string('api_fleet_fields_map', 4096)->nullable();//json object
            $table->boolean('api_vessel_can_timeout')->default(0);
            $table->integer('api_vessel_timeout')->default(0);
            $table->integer('api_ais_alert_timeout')->default(30);
            $table->string('vessel_update_cron', 512)->nullable();//json object
            $table->string('fleet_update_cron', 512)->nullable();//json object
            $table->boolean('api_update_using_name')->default(0);
            $table->boolean('mail_notifications')->default(1);
            $table->boolean('sms_notifications')->default(0);
            $table->boolean('wfs')->default(0);//Web Feature Services
            $table->boolean('active')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vtrac_providers');
    }
}
