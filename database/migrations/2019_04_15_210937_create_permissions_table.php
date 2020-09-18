<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('role_id');
            $table->unsignedInteger('system_component_id');
            $table->string('permissions')->nullable()->default(null);
            $table->timestamps();

            $table->unique(['system_component_id', 'role_id'], 'permissions_system_component_id_role_id_unique');

            $table->foreign('system_component_id', 'permissions_system_component_id')
                ->references('id')->on('system_components')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('role_id', 'permissions_role_id')
                ->references('id')->on('roles')
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
        Schema::dropIfExists('permissions');
    }
}
