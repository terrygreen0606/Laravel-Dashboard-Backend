<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAisStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nav_statuses', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->unsignedInteger('status_id');
            $table->string('value', 512);
            $table->timestamps();

            $table->unique('status_id', 'status_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('nav_statuses');
    }
}
