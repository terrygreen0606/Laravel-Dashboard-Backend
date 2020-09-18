<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompanyAddressesTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $set_schema_table = 'company_addresses';

    /**
     * Run the migrations.
     * @table company_addresses
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->set_schema_table)) return;
        Schema::create($this->set_schema_table, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('address_type_id')->default(0);
            $table->unsignedInteger('company_id');
            $table->string('co')->nullable()->default(null);
            $table->string('street')->nullable()->default(null);
            $table->string('unit')->nullable()->default(null);
            $table->string('city')->nullable()->default(null);
            $table->string('province')->nullable()->default(null);
            $table->string('state')->nullable()->default(null);
            $table->string('country')->nullable()->default(null);
            $table->string('zip')->nullable()->default(null);
            $table->string('phone')->nullable()->default(null);
            $table->double('latitude', 10, 6)->nullable()->default(null);
            $table->double('longitude', 10, 6)->nullable()->default(null);
            $table->unsignedInteger('zone_id')->nullable()->default(null);
            $table->text('document_format')->default(null);
            $table->boolean('unverified')->default(0);
            $table->timestamps();

            $table->index(['address_type_id'], 'address_type_id');
            $table->index(['company_id'], 'company_id');

            $table->foreign('address_type_id', 'company_addresses_address_type_id')
                ->references('id')->on('address_types')
                ->onDelete('no action')
                ->onUpdate('cascade');

            $table->foreign('company_id', 'company_addresses_company_id')
                ->references('id')->on('companies')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('zone_id', 'company_addresses_zone_id')
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
        Schema::dropIfExists($this->set_schema_table);
    }
}
