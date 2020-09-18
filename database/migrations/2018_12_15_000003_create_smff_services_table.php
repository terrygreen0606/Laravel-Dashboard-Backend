<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSmffServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('smff_services', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->boolean('s_remote_assessment_and_consultation')->default(0);
            $table->boolean('s_begin_assessment_of_structural_stability')->default(0);
            $table->boolean('s_onsite_salvage_assessment')->default(0);
            $table->boolean('s_assessment_of_structural_stability')->default(0);
            $table->boolean('s_hull_and_bottom_survey')->default(0);
            $table->boolean('s_emergency_towing')->default(0);
            $table->boolean('s_salvage_plan')->default(0);
            $table->boolean('s_external_emergency_transfer_operations')->default(0);
            $table->boolean('s_emergency_lightering')->default(0);
            $table->boolean('s_capacity_bbl')->default(0);
            $table->string('s_capacity_bbl_value')->default(null);
            $table->boolean('s_other_refloating_methods')->default(0);
            $table->boolean('s_making_temporary_repairs')->default(0);
            $table->boolean('s_diving_services_support')->default(0);
            $table->boolean('s_special_salvage_operations_plan')->default(0);
            $table->boolean('s_subsurface_product_removal')->default(0);
            $table->boolean('s_heavy_lift')->default(0);
            $table->string('s_tug_type')->nullable()->default(null);
            $table->integer('s_horsepower')->nullable()->default(null);
            $table->integer('s_bollard_pull')->nullable()->default(null);
            $table->string('s_subchapter_m')->nullable()->default(null);
            $table->float('s_lifting_gear_minimum_swl')->nullable()->default(null);
            $table->string('s_largest_cargo_gear')->nullable()->default(null);
            $table->integer('s_lifting_gear_reach')->nullable()->default(null);
            $table->integer('s_available_deck_space')->nullable()->default(null);
            $table->string('s_available_deck_space_location')->nullable()->default(null);
            $table->boolean('mff_remote_assessment_and_consultation')->default(0);
            $table->boolean('mff_onsite_fire_assessment')->default(0);
            $table->boolean('mff_external_firefighting_teams')->default(0);
            $table->boolean('mff_external_vessel_firefighting_systems')->default(0);
            $table->string('mff_class_classification')->nullable()->default(null);
            $table->integer('mff_pumping_capacity')->nullable()->default(null);
            $table->integer('mff_foam_quantity')->nullable()->default(null);
            $table->boolean('acd_logistics_asset')->default(0);
            $table->integer('acd_passenger_capacity')->nullable()->default(null);
            $table->integer('acd_heli_landing_capacity')->nullable()->default(null);
            $table->integer('acd_heli_drop_zone_size')->nullable()->default(null);
            $table->integer('acd_small_boat_size')->nullable()->default(null);
            $table->integer('acd_small_boat_capacity')->nullable()->default(null);
            $table->string('acd_medical_personnel_onboard')->nullable()->default(null);
            $table->string('acd_radio_watch_schedule')->nullable()->default(null);
            $table->string('acd_gsa_designator')->nullable()->default(null);
            $table->string('primary_service')->nullable()->default('all_services');
            $table->text('notes')->nullable()->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('smff_services');
    }
}
