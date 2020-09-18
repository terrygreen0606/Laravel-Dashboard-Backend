<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompanyContactsTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $set_schema_table = 'company_contacts';

    /**
     * Run the migrations.
     * @table company_contacts
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->set_schema_table)) return;
        Schema::create($this->set_schema_table, function (Blueprint $table) {
            
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('prefix')->nullable()->default(null);
            $table->string('first_name')->nullable()->default(null);
            $table->string('last_name')->nullable()->default(null);
            $table->string('email')->nullable()->default(null);
            $table->string('work_phone')->nullable()->default(null);
            $table->string('mobile_phone')->nullable()->default(null);
            $table->string('aoh_phone')->nullable()->default(null);
            $table->string('fax')->nullable()->default(null);
            $table->string('position')->nullable()->default(null);
            $table->string('company_role')->nullable()->default(null);
            $table->string('notes')->nullable()->default(null);
            $table->unsignedInteger('company_id');
            $table->timestamps();

            $table->index(['company_id'], 'company_id');


            $table->foreign('company_id', 'company_contacts_company_id')
                ->references('id')->on('companies')
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
