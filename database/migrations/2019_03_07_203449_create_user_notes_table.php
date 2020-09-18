<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_notes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->text('note')->nullable()->default(null);
            $table->integer('note_type')->nullable()->default(null);
            $table->unsignedInteger('creator_id')->nullable()->default(null);
            $table->unsignedInteger('user_id');
            $table->timestamps();

            $table->index(['creator_id'], 'creator_id');
            $table->index(['user_id'], 'user_id');


            $table->foreign('creator_id', 'user_notes_creator_id')
                ->references('id')->on('users')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->foreign('user_id', 'user_notes_user_id')
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
        Schema::dropIfExists('user_notes');
    }
}
