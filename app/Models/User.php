<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Arr;
use Laravel\Scout\Searchable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    use Searchable;

    const FIELDS_SELECT = '
        u.id,
        0 AS vrp_import,
        u.active,
        u.active AND c.active AS djs_active,
        c.networks_active,
        us.id IS NOT NULL AS capabilies_active,
        primary_company_id,
        u.email,
        u.title,
        u.suffix,
        u.first_name,
        u.last_name,
        u.mobile_number,
        u.work_phone,
        u.aoh_phone,
        u.fax,
        u.alternate_email,
        u.username,
        u.occupation,
        u.resume_link,
        u.description,
        u.has_photo,
        u.role_id,
        us.id IS NOT NULL AS resource_provider,
        us.id IS NOT NULL AS response,
        c.id AS company_name,
        c.name AS company_name,
        c.active AS company_active,
        c.name LIKE "%donjon%" AS coverage
    ';


    const FIELDS_SELECT_ADDRESS = '
        a.id AS id,
        user_id,
        0 AS vrp_import,
        u.active AND c.active AS djs_active,
        c.networks_active,
        us.id IS NOT NULL AS capabilies_active,
        u.id AS user_id,
        u.email,
        u.title,
        u.suffix,
        u.first_name,
        u.last_name,
        u.mobile_number,
        u.work_phone,
        u.aoh_phone,
        u.fax,
        u.alternate_email,
        u.username,
        u.occupation,
        u.resume_link,
        u.description,
        us.id IS NOT NULL AS resource_provider,
        us.id IS NOT NULL AS response,
        c.id AS company_name,
        c.name AS company_name,
        c.active AS company_active,
        c.name LIKE "%donjon%" AS coverage,
        latitude,
        longitude
    ';

    public function table() {
        return $this->getConnection()->getDatabaseName() . '.' . $this->getTable();
    }



    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'last_name', 'username', 'email', 'password', 'active', 'vendor_id', 'primary_company_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function hasVRP() {
        return $this->isAdminOrDuty();
    }

    public function isAdminOrDuty() {
        if($this->role_id == Role::ADMIN || $this->role_id == Role::DUTY_TEAM || $this->role_id == Role::NAVY_NASA) {
            return true;
        } else {
            return false;
        }
        // return $this->roles->whereIn('id', [Role::ADMIN, Role::DUTY_TEAM, Role::NAVY_NASA])->count() > 0;
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return Arr::only($this->toArray(), ['first_name', 'last_name', 'email', 'username']);
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function roles()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function contactTypes()
    {
        return $this->belongsToMany(ContactType::class, 'contact_has_types', 'contact_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function fleets()
    {
        return $this->belongsToMany(Fleet::class, 'fleets_users');
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'companies_users');
    }

  /*  public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }*/

    public function companyNotes()
    {
        return $this->hasMany(CompanyNotes::class);
    }

    public function vesselNotes()
    {
        return $this->hasMany(VesselNotes::class);
    }

    public function userNotes()
    {
        return $this->hasMany(UserNote::class);
    }

    public function address()
    {
        return $this->hasOne(UserAddress::class);
    }

    public function smffCapability()
    {
        return $this->hasOne(Capability::class, 'id', 'smff_service_id');
    }

    public function networks()
    {
        return $this
            ->belongsToMany(Network::class, 'network_companies', 'company_id', 'network_id', 'primary_company_id')
            ->select(['companies.*', 'networks.code'])
            ->join('companies', 'company_id', '=', 'companies.id')
            ->where('companies.networks_active', 1);
    }

    // FIXME: the same one as above, above for map/networks-filter, below for map/search/individuals function
    // this is hotfix, need to be fixed later
    public function cnetworks()
    {
        return $this
            ->belongsToMany(Network::class, 'network_companies', 'company_id', 'network_id', 'primary_company_id')
            ->select(['companies.*', 'networks.code'])
            ->join('companies', 'company_id', '=', 'companies.id')
            ->where('companies.networks_active', 1);
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

    public function companiesPOC()
    {
        return $this->hasMany(Company::class, 'company_poc_id', 'id');
    }


    public function vesselsAsPrimaryPoc()
    {
        return $this->hasMany(Vessel::class, 'primary_poc_id');
    }

    public function vesselsAsSecondaryPoc()
    {
        return $this->hasMany(Vessel::class, 'secondary_poc_id');
    }

    public function drops()
    {
        return $this->hasMany(Drop::class);
    }

    public function primaryCompany()
    {
        return $this->belongsTo(Company::class, 'primary_company_id')->withDefault([
            'id' => NULL,
            'has_photo' => FALSE,
        ]);
    }
}
