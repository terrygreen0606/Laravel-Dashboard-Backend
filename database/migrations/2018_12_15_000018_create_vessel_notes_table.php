<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVesselNotesTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $set_schema_table = 'vessel_notes';

    /**
     * Run the migrations.
     * @table vessel_notes
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->set_schema_table)) return;
        Schema::create($this->set_schema_table, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->text('note')->nullable()->default(null);
            $table->integer('note_type')->nullable()->default(null);
            $table->unsignedInteger('user_id')->nullable()->default(null);
            $table->unsignedInteger('vessel_id');
            $table->dateTime('date_created')->nullable();//backward capability with the old DJS
            $table->timestamps();

            $table->index(['user_id'], 'creator_user_id');
            $table->index(['vessel_id'], 'vessel_id');


            $table->foreign('user_id', 'vessel_notes_creator_user_id')
                ->references('id')->on('users')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->foreign('vessel_id', 'vessel_notes_vessel_id')
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
        Schema::dropIfExists($this->set_schema_table);
    }
}
