<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactHasTypesTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $set_schema_table = 'contact_has_types';

    /**
     * Run the migrations.
     * @table contact_has_types
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->set_schema_table)) return;
        Schema::create($this->set_schema_table, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->unsignedInteger('contact_id');
            $table->unsignedInteger('contact_type_id');
            $table->timestamps();

            $table->unique(['contact_id', 'contact_type_id'], 'contact_has_types_contact_id_contact_type_id_unique');

            $table->foreign('contact_id', 'contact_has_types_contact_id')
                ->references('id')->on('company_contacts')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('contact_type_id', 'contact_has_types_contact_type_id')
                ->references('id')->on('contact_types')
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
