<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVendorsTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $set_schema_table = 'vendors';

    /**
     * Run the migrations.
     * @table vendors
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->set_schema_table)) return;
        Schema::create($this->set_schema_table, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('vendor_type_id')->nullable()->default(null);
            $table->string('name');
            $table->string('shortname')->nullable()->default(null);
            $table->string('company_email')->nullable()->default(null);
            $table->string('phone')->nullable()->default(null);
            $table->string('fax')->nullable()->default(null);
            $table->string('notes')->nullable()->default(null);
            $table->string('address')->nullable()->default(null);
            $table->string('city')->nullable()->default(null);
            $table->string('city_state_pc')->nullable()->default(null);
            $table->string('state')->nullable()->default(null);
            $table->string('country')->nullable()->default(null);
            $table->string('zip')->nullable()->default(null);
            $table->timestamps();

            $table->index(['vendor_type_id'], 'vendor_type_id');

            $table->foreign('vendor_type_id', 'vendors_vendor_type_id')
                ->references('id')->on('vendor_types')
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
