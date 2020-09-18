<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Company;
use App\Models\Vessel;
use App\Models\VesselListIndex;
use Illuminate\Support\Facades\DB;

class BaseModel extends Model
{

    public function db() {
        return $this->getConnection()->getDatabaseName();
    }

    public function table() {
        return $this->getConnection()->getDatabaseName() . '.' . $this->getTable();
    }


    public function updateIndexVessels($new = true) {
        if ($new) {
            VesselListIndex::deleteAll();

            $cdtVesselsTable = new Vessel;
            $cdtVesselTableName = $cdtVesselsTable->getConnection()->getDatabaseName() . "." . $cdtVesselsTable->getTable();
            $vrpVesselTable = new VrpVessel;
            $vrpVesselTableName = $vrpVesselTable->getConnection()->getDatabaseName() . "." . $vrpVesselTable->getTable();

            $companyTable = new Company;
            $companyTableName = $companyTable->getConnection()->getDatabaseName() . "." . $companyTable->getTable();

            $vesselTypeTable = new VesselType;
            $vesselTypeTableName = $vesselTypeTable->getConnection()->getDatabaseName() . "." . $vesselTypeTable->getTable();

            $planTable = new VrpPlan;
            $planTableName = $planTable->getConnection()->getDatabaseName() . "." . $planTable->getTable();

            $indexTable = new VesselListIndex;
            $indexTableName = $indexTable->getConnection()->getDatabaseName() . "." . $indexTable->getTable();


            DB::update(
                "INSERT INTO " . $indexTableName . " (select " . Vessel::UNION_FIELDS_CDT . "
                    from " . $cdtVesselTableName . " as `v1`
                    inner join " . $vrpVesselTableName . " as `vrpv` on `v1`.`imo` = `vrpv`.`imo`
                    inner join " . $vesselTypeTableName . " as `t` on `v1`.`vessel_type_id` = `t`.`id`
                    left join " . $companyTableName . " as `c1` on `v1`.`company_id` = `c1`.`id`)"
                );

        } else {

        }
    }
}
