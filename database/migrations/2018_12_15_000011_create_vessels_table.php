<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVesselsTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $set_schema_table = 'vessels';

    /**
     * Run the migrations.
     * @table vessels
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->set_schema_table)) return;
        Schema::create($this->set_schema_table, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('imo')->nullable()->default(null);
            $table->integer('mmsi')->nullable()->default(null);
            $table->integer('official_number')->nullable()->default(null);
            $table->string('name');
            $table->boolean('has_photo')->default(0);

            $table->double('latitude', 10, 6)->nullable()->default(null);
            $table->double('longitude', 10, 6)->nullable()->default(null);
            $table->double('speed', 10, 6)->nullable()->default(null);
            $table->double('heading', 10, 6)->nullable()->default(null);
            $table->double('course', 10, 6)->nullable()->default(null);
            $table->unsignedInteger('zone_id')->nullable()->default(null);
            $table->unsignedInteger('ais_nav_status_id')->nullable()->default(null);
            $table->unsignedInteger('ais_provider_id')->nullable();
            $table->string('ais_source')->nullable();
            $table->string('ais_timestamp')->nullable();

            $table->string('destination')->nullable();
            $table->string('eta')->nullable();

            $table->string('cargo_type')->nullable()->default(null);
            $table->string('dead_weight')->nullable()->default(null);
            $table->decimal('deck_area', 13, 2)->nullable()->default(null);
            $table->decimal('oil_tank_volume', 13, 2)->nullable()->default(null);
            $table->string('oil_group')->nullable()->default(null);
            $table->boolean('tanker')->default(0);
            $table->boolean('lead_ship')->default(0);
            $table->integer('lead_ship_id')->nullable()->default(null);
            $table->unsignedInteger('company_id')->nullable()->default(null);
            $table->unsignedInteger('primary_poc_id')->nullable()->default(null);
            $table->unsignedInteger('secondary_poc_id')->nullable()->default(null);
            $table->unsignedInteger('vessel_type_id')->nullable()->default(null);
            $table->string('lead_ship_name')->nullable()->default(null);
            $table->integer('lead_sister_ship_id')->nullable()->default(null);

            $table->string('sat_phone_primary')->nullable()->default(null);
            $table->string('sat_phone_secondary')->nullable()->default(null);
            $table->string('email_primary')->nullable()->default(null);
            $table->string('email_secondary')->nullable()->default(null);

            $table->unsignedInteger('smff_service_id')->nullable()->default(null);

            $table->boolean('active')->default(1);
            $table->double('construction_length_overall', 10, 6)->nullable()->default(null);
            $table->double('construction_length_bp', 10, 6)->nullable()->default(null);
            $table->double('construction_length_reg', 10, 6)->nullable()->default(null);
            $table->boolean('construction_bulbous_bow')->default(0);
            $table->double('construction_breadth_extreme', 10, 6)->nullable()->default(null);
            $table->double('construction_breadth_moulded', 10, 6)->nullable()->default(null);
            $table->double('construction_draught', 10, 6)->nullable()->default(null);
            $table->double('construction_depth', 10, 6)->nullable()->default(null);
            $table->double('construction_height', 10, 6)->nullable()->default(null);
            $table->double('construction_tcm', 10, 6)->nullable()->default(null);
            $table->double('construction_displacement', 10, 6)->nullable()->default(null);
            $table->timestamps();

            $table->index(['ais_nav_status_id'], 'ais_nav_status_id');

            $table->index(['vessel_type_id'], 'vessel_type_id');

            $table->index(['secondary_poc_id'], 'secondary_poc_id');

            $table->index(['company_id'], 'company_id');

            $table->index(['primary_poc_id'], 'primary_poc_id');

            $table->index(['smff_service_id'], 'smff_service_id');


            $table->foreign('ais_nav_status_id', 'vessels_ais_status_id')
                ->references('status_id')->on('nav_statuses')
                ->onDelete('no action')
                ->onUpdate('cascade');

            $table->foreign('company_id', 'vessels_vessels_company_id')
                ->references('id')->on('companies')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->foreign('primary_poc_id', 'vessels_primary_poc_id')
                ->references('id')->on('company_contacts')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->foreign('secondary_poc_id', 'vessels_secondary_poc_id')
                ->references('id')->on('company_contacts')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->foreign('vessel_type_id', 'vessels_vessel_type_id')
                ->references('id')->on('vessel_types')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->foreign('smff_service_id', 'vessels_smff_service_id')
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
