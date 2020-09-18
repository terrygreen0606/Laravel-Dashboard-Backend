<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVesselsHistoricalTrack extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vessels_historical_track', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('vessel_id');
            $table->double('latitude', 10, 6)->nullable()->default(null);
            $table->double('longitude', 10, 6)->nullable()->default(null);
            $table->double('speed', 10, 6)->nullable()->default(null);
            $table->double('heading', 10, 6)->nullable()->default(null);
            $table->double('course', 10, 6)->nullable()->default(null);
            $table->unsignedInteger('ais_status_id')->default(0);
            $table->string('ais_source')->nullable();
            $table->timestamps();

            $table->index(['vessel_id'], 'vessel_id');


            $table->foreign('vessel_id', 'historical_track_vessel_id')
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
        Schema::dropIfExists('vessels_historical_track');
    }
}
