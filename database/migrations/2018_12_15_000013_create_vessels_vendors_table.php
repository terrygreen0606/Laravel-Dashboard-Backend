<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVesselsVendorsTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $set_schema_table = 'vessels_vendors';

    /**
     * Run the migrations.
     * @table vessels_vendors
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->set_schema_table)) return;
        Schema::create($this->set_schema_table, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->unsignedInteger('vessel_id');
            $table->unsignedInteger('vendor_id');
            $table->timestamps();

            $table->unique(['vessel_id', 'vendor_id'], 'vessels_vendors_vessel_id_vendor_id_unique');


            $table->foreign('vessel_id', 'vessels_vendors_vessel_id')
                ->references('id')->on('vessels')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('vendor_id', 'vessels_vendors_vendor_id')
                ->references('id')->on('vendors')
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
