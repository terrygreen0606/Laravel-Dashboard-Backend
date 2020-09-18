<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNetworkCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('network_companies', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('network_id');
            $table->unsignedInteger('company_id');
            $table->timestamps();

            $table->unique(['network_id', 'company_id'], 'network_companies_network_id_company_id_unique');


            $table->foreign('network_id', 'network_companies_network_id')
                ->references('id')->on('networks')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('company_id', 'network_companies_company_id')
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
        Schema::dropIfExists('network_companies');
    }
}
