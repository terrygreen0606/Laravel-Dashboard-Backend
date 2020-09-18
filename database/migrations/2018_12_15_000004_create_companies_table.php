<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompaniesTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $set_schema_table = 'companies';

    /**
     * Run the migrations.
     * @table companies
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->set_schema_table)) return;
        Schema::create($this->set_schema_table, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('plan_number')->nullable()->default(null);
            $table->string('name');
            $table->string('email')->nullable()->default(null);
            $table->string('fax')->nullable()->default(null);
            $table->string('phone')->nullable()->default(null);
            $table->text('notes')->nullable()->default(null);
            $table->string('website')->nullable()->default(null);
            $table->string('photo')->nullable()->default(null);
            $table->text('description')->nullable()->default(null);
            $table->boolean('active')->default(1);
            $table->unsignedInteger('qi_id')->nullable()->default(null);
            $table->unsignedInteger('operating_company_id')->nullable()->default(null);
            $table->unsignedInteger('smff_service_id')->nullable()->default(null);
            $table->unsignedInteger('company_poc_id')->nullable()->default(null);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['operating_company_id'], 'operating_company_id');

            $table->index(['qi_id'], 'qi_id');

            $table->index(['smff_service_id'], 'smff_service_id');

            $table->foreign('qi_id', 'companies_qi_id')
                ->references('id')->on('vendors')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->foreign('operating_company_id', 'companies_operating_company_id')
                ->references('id')->on('companies')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->foreign('smff_service_id', 'companies_smff_service_id')
                ->references('id')->on('smff_services')
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
        Schema::dropIfExists($this->set_schema_table);
    }
}
