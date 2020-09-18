<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserAddressTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_address', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('co')->nullable()->default(null);
            $table->string('street')->nullable()->default(null);
            $table->string('unit')->nullable()->default(null);
            $table->string('city')->nullable()->default(null);
            $table->string('province')->nullable()->default(null);
            $table->string('state')->nullable()->default(null);
            $table->string('country')->nullable()->default(null);
            $table->string('zip')->nullable()->default(null);
            $table->double('latitude', 10, 6)->nullable()->default(null);
            $table->double('longitude', 10, 6)->nullable()->default(null);
            $table->unsignedInteger('zone_id')->nullable()->default(null);
            $table->timestamps();

            $table->index(['user_id'], 'user_id');
            $table->foreign('user_id', 'user_address_user_id')
                ->references('id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('zone_id', 'user_address_zone_id')
                ->references('id')->on('zones')
                ->onDelete('set null')
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
        Schema::dropIfExists('user_address');
    }
}
