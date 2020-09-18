<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Laravel\Scout\Searchable;

class Company extends BaseModel
{
    use Searchable;
    protected $table = 'companies';
    protected $guarded = [];

    const FIELDS_COMPANY = 'c1.id, 0 AS vrpid, 0 AS vrp_import, c1.active AS djs_active, c1.has_photo, c1.networks_active, c1.vendor_active, c1.shortname, cs.id IS NOT NULL AS capabilies_active, "" AS vrp_primary_smff, "Vendor" AS vendor_category, 0 AS linked, 1 AS djs, 0 AS auth, c1.name AS name, 0 AS vrp_plan_name, c1.plan_number, c1.plan_number AS vrp_plan_number, cs.id IS NOT NULL AS resource_provider, c1.active, 0 AS vrp_status, c1.active AS coverage, cs.id AS smff_service_id, 0 AS vrp_plan_type, 0 AS vrp_country, qi_id, operating_company_id, plan_preparer_id, email, fax, phone, website, description, c1.created_at, c1.updated_at';

    const UNION_FIELDS_COMPANY ='c1.id, p1.id AS vrpid, 0 AS vrp_import, c1.active AS djs_active, c1.networks_active, c1.vendor_active, cs.id IS NOT NULL AS capabilies_active, p1.primary_smff AS vrp_primary_smff, "Vendor" AS vendor_category, p1.id IS NOT NULL AS linked, 1 AS djs, p1.status = "Authorized" AS auth, c1.name AS name, p1.plan_holder AS vrp_plan_name, c1.plan_number, c1.plan_number AS vrp_plan_number, cs.id IS NOT NULL AS resource_provider, c1.active, p1.status AS vrp_status, c1.active AS coverage, cs.id AS smff_service_id, p1.plan_type AS vrp_plan_type, p1.holder_country AS vrp_country, qi_id, operating_company_id, email, fax, phone, website, description, c1.created_at, c1.updated_at';

    const UNION_FIELDS_PLAN = '-1 AS id, p2.id AS vrpid, 1 AS vrp_import, 0 AS djs_active, 0 AS networks_active, 0 AS vendor_active, 0 AS capabilies_active, p2.primary_smff AS vrp_primary_smff, "" AS vendor_category, 0 AS linked, 0 AS djs, p2.status = "Authorized" AS auth, plan_holder AS name, plan_holder AS vrp_plan_name, p2.plan_number, p2.plan_number AS vrp_plan_number, 0 AS resource_provider, 0 AS active, p2.status AS vrp_status, plan_holder LIKE "%donjon%" AS coverage, 0 AS smff_service_id, plan_type AS vrp_plan_type, p2.holder_country AS vrp_country, 0 AS qi_id, 0 AS operating_company_id, "" AS email, "" AS fax, "" AS phone, "" AS website, "" AS description, p2.created_at, p2.updated_at';

    const FIELDS_ADDRESS = 'company_addresses.id AS id, company_addresses.zone_id, company_addresses.street, company_addresses.city, company_addresses.state, company_addresses.country, c.id AS company_id, c.name, latitude, longitude, smff_service_id, c.email, c.phone, cs.primary_service';


    const TYPE_HANDM = 1;
    const TYPE_PANDI = 2;
    const TYPE_QI = 3;
    const TYPE_RESPONSE = 4;
    const TYPE_DAMAGE = 5;
    const TYPE_SOCIETY = 6;
    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return Arr::only($this->toArray(), ['name', 'plan_number']);
    }

    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            //$model->updateIndexVessels(false);
        });
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'companies_users');
    }

    /*public function individuals()
    {
        return $this->hasMany(User::class, 'company_id');
    }*/

    public function companyPOC()
    {
        return $this->belongsTo(User::class, 'company_poc_id');
    }

    public function addresses()
    {
        return $this->hasMany(CompanyAddress::class);
    }

    public function notes()
    {
        return $this->hasMany(CompanyNotes::class);
    }

    public function primaryAddress()
    {
        return $this->addresses()->whereHas('addressType', function ($q) {
            $q->where('name', 'Primary');
        });
    }

    public function vessels()
    {
        return $this->hasMany(Vessel::class);
    }

    public function vendorVessels()
    {
        return $this->belongsToMany(Company::class, 'vessels_vendors', 'company_id', 'vessel_id');
    }

    public function contacts()
    {
        return $this->users()->whereHas('contactTypes');
    }

    public function hasuser_data()
    {
        return $this->hasMany(User::class,'company_id','id');
    }

    public function primaryContacts()
    {
        return $this->users()->whereHas('contactTypes', function ($q) {
            $q->where('name', 'Primary');
        });
    }

    public function secondaryContacts()
    {
        return $this->users()->whereHas('contactTypes', function ($q) {
            $q->where('name', 'Secondary');
        });
    }

    public function dpaContacts()
    {
        return $this->users()->whereHas('contactTypes', function ($q) {
            $q->where('name', 'DPA');
        });
    }

    public function vendor()
    {
        return Company::whereId($this->id)->first();
    }

    public function operatingCompany()
    {
        return $this->belongsTo(__CLASS__, 'operating_company_id');
    }

    public function companies()
    {
        return $this->hasMany(__CLASS__, 'operating_company_id', 'id');
    }

    public function smffCapability()
    {
        return $this->hasOne(Capability::class, 'id', 'smff_service_id');
    }

    public function smff() {
        $smff_service_id = $this->smff_service_id;
        if ($smff_service_id) {
            $capability = Capability::where(['id' => $smff_service_id, 'status' => 1])->first();
            if (!empty($capability)) {
                return $capability->valuesAsAssoc();
            }
        }

        return null;
    }

    public function networks()
    {
        return $this->belongsToMany(Network::class, 'network_companies')
            ->select(['companies.*', 'networks.code'])
            ->join('companies', 'network_companies.company_id', '=', 'companies.id')
            ->where('networks_active', 1);
    }

    public function vrpPlan()
    {
        return $this->hasOne(Vrp\VrpPlan::class, 'plan_number', 'plan_number');
    }

    public function type()
    {
        return $this->hasOne(VendorType::class, 'id','vendor_type');
    }

    public function hm()
    {
        return $this->type()->where('name', 'H&M Insurer');
    }

    public function planPreparer()
    {
        return $this->belongsTo(PlanPreparer::class, 'plan_preparer_id');
    }
}
