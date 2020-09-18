<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNetworkUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('network_users', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('network_id');
            $table->unsignedInteger('user_id');
            $table->timestamps();

            $table->unique(['network_id', 'user_id'], 'network_users_network_id_user_id_unique');


            $table->foreign('network_id', 'network_users_network_id')
                ->references('id')->on('networks')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('user_id', 'network_users_user_id')
                ->references('id')->on('users')
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
        Schema::dropIfExists('network_users');
    }
}
