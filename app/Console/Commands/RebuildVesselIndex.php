<?php

namespace App\Console\Commands;

use DB;
use DateTime;
use Illuminate\Console\Command;

class RebuildVesselIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdt:rebuild-vessel-index {--vessel_id=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add vessel for continuous MT poll';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // todo: make update index for non-imo/officical number updates 
        $vessel_id = $this->option('vessel_id');
       DB::transaction(function () {
       DB::statement('DELETE FROM vessels_list_index');

        DB::statement('INSERT INTO vessels_list_index  (select distinct v1.id AS id, vrpv.id AS vrpid, 0 AS vrp_import, (v1.active AND c1.active) AS djs_active, c1.networks_active, vs.id IS NOT NULL AS capabilies_active, p.primary_smff AS vrp_primary_smff, 1 AS djs, vrpv.id IS NOT NULL AS linked, vrpv.vessel_status = "Authorized" AS auth, v1.imo, v1.official_number, v1.name, 1 AS vrp_count, vrpv.vessel_status AS vrp_status, c1.plan_number AS vrp_plan_number, c1.id AS company_id, c1.id AS vrp_company_id, c1.name AS company_name, c1.plan_number AS company_plan_number, c1.active AS company_active, vrpv.vessel_is_tank LIKE "%Tank%" AS vrp_vessel_is_tank, t.name AS vrp_type, vs.id IS NOT NULL AS resource_provider, v1.active, v1.active AS coverage,  v1.smff_service_id IS NOT NULL OR c1.smff_service_id IS NOT NULL AS response, v1.vessel_type_id, v1.mmsi, dead_weight, tanker, deck_area, oil_tank_volume, oil_group, primary_poc_id, secondary_poc_id, sat_phone_primary, sat_phone_secondary, email_primary, email_secondary, v1.created_at, v1.updated_at, v1.updated_at AS vessels_updated_at from `cdt_import`.`vessels` as `v1` left join `cdt_import`.`vessel_types` as `t` on `v1`.`vessel_type_id` = `t`.`id` left join `cdt_import`.`companies` as `c1` on `v1`.`company_id` = `c1`.`id` left join `capabilities` as `vs` on `v1`.`smff_service_id` = `vs`.`id` and `vs`.`status` = 1 left join `cdt_vrp`.`vessels_data` as `vrpv` on `v1`.`imo` = `vrpv`.`imo` and `c1`.`plan_number` = `vrpv`.`plan_number` left join `cdt_vrp`.`vrp_plan` as `p` on `vrpv`.`plan_number` = `p`.`plan_number` where `v1`.`official_number` is null)');

        DB::statement('INSERT INTO vessels_list_index  (select distinct v1.id AS id, vrpv.id AS vrpid, 0 AS vrp_import, (v1.active AND c1.active) AS djs_active, c1.networks_active, vs.id IS NOT NULL AS capabilies_active, p.primary_smff AS vrp_primary_smff, 1 AS djs, vrpv.id IS NOT NULL AS linked, vrpv.vessel_status = "Authorized" AS auth, v1.imo, v1.official_number, v1.name, 1 AS vrp_count, vrpv.vessel_status AS vrp_status, c1.plan_number AS vrp_plan_number, c1.id AS company_id, c1.id AS vrp_company_id, c1.name AS company_name, c1.plan_number AS company_plan_number, c1.active AS company_active, vrpv.vessel_is_tank LIKE "%Tank%" AS vrp_vessel_is_tank, t.name AS vrp_type, vs.id IS NOT NULL AS resource_provider, v1.active, v1.active AS coverage,  v1.smff_service_id IS NOT NULL OR c1.smff_service_id IS NOT NULL AS response, v1.vessel_type_id, v1.mmsi, dead_weight, tanker, deck_area, oil_tank_volume, oil_group, primary_poc_id, secondary_poc_id, sat_phone_primary, sat_phone_secondary, email_primary, email_secondary, v1.created_at, v1.updated_at, v1.updated_at AS vessels_updated_at from `cdt_import`.`vessels` as `v1` left join `cdt_import`.`vessel_types` as `t` on `v1`.`vessel_type_id` = `t`.`id` left join `cdt_import`.`companies` as `c1` on `v1`.`company_id` = `c1`.`id` left join `capabilities` as `vs` on `v1`.`smff_service_id` = `vs`.`id` and `vs`.`status` = 1 left join `cdt_vrp`.`vessels_data` as `vrpv` on `v1`.`official_number` = `vrpv`.`official_number` and `c1`.`plan_number` = `vrpv`.`plan_number` left join `cdt_vrp`.`vrp_plan` as `p` on `vrpv`.`plan_number` = `p`.`plan_number` where `v1`.`official_number` is not null)');

        DB::statement('INSERT INTO vessels_list_index (select distinct -1 AS id, vrpv2.id AS vrpid, 1 AS vrp_import, 0 AS djs_active, 0 AS networks_active, 0 AS capabilies_active, p.primary_smff AS vrp_primary_smff, 0 AS djs, 0 AS linked, vrpv2.vessel_status = "Authorized" AS auth, vrpv2.imo, vrpv2.official_number, vessel_name AS name, 1 AS vrp_count, vrpv2.vessel_status AS vrp_status, vrpv2.plan_number AS vrp_plan_number, NULL AS company_id, c2.id AS vrp_company_id, NULL AS company_name, NULL AS company_plan_number, NULL AS company_active, vrpv2.vessel_is_tank LIKE "%Tank%" AS vrp_vessel_is_tank, vessel_type AS vrp_type, 0 AS resource_provider, 0 AS active, p.primary_smff LIKE "%donjon%" AS coverage,  0 AS response, NULL AS vessel_type_id, NULL AS  mmsi, NULL AS  dead_weight, NULL AS  tanker, NULL AS  deck_area, NULL AS  oil_tank_volume, NULL AS  oil_group, NULL AS  primary_poc_id, NULL AS  secondary_poc_id, NULL AS  sat_phone_primary, NULL AS  sat_phone_secondary, NULL AS  email_primary, NULL AS  email_secondary, vrpv2.created_at, vrpv2.updated_at, vrpv2.updated_at AS vessels_updated_at from `cdt_vrp`.`vessels_data` as `vrpv2` left join `cdt_vrp`.`vrp_plan` as `p` on `vrpv2`.`plan_number_id` = `p`.`id` left join `cdt_import`.`companies` as `c2` on `p`.`plan_number` = `c2`.`plan_number` where ((vrpv2.imo IS NOT NULL AND vrpv2.imo NOT IN   (SELECT imo FROM cdt.vessels AS vx   left join `cdt_import`.`companies` as `cx` on `vx`.`company_id` = `cx`.`id`   WHERE vx.imo IS NOT NULL AND vrpv2.plan_number = cx.plan_number)) OR (vrpv2.imo IS NULL AND vrpv2.official_number IS NOT NULL AND vrpv2.official_number NOT IN   (SELECT official_number FROM cdt.vessels AS vx2   left join `cdt_import`.`companies` as `cx2` on `vx2`.`company_id` = `cx2`.`id`   WHERE vx2.official_number IS NOT NULL AND vrpv2.plan_number = cx2.plan_number  ))))');    

    }, 1);

    }
}
