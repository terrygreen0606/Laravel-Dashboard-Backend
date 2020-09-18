<?php

namespace App\Models\Vrp;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class VrpPlan extends \App\Models\BaseModel
{
    use Searchable;
    protected $connection = "mysql_vrp";
    protected $table = 'vrp_plan';
    protected $guarded = [];

    public function toSearchableArray()
    {
        return array_only($this->toArray(), ['plan_number', 'plan_holder']);
    }

    public function Vessels()
    {
        return $this->hasMany('App\Models\Vrp\Vessel', 'plan_number_id', 'id');
    }

    public function QualifiedIndividual()
    {
        return $this->belongsTo('App\Models\Vrp\QualifiedIndividual', 'id', 'vrp_qi_id');
    }

    public function CotpZone()
    {
        return $this->hasMany('App\Models\Vrp\CotpZone', 'vrp_plan_id', 'plan_number');
    }

    public function ProviderChanges()
    {
        return $this->hasMany('App\Models\Vrp\ProviderChanges', 'plan_id', 'id');
    }

    public function QIProviderChanges()
    {
        return $this->hasMany('App\Models\Vrp\QIProviderChanges', 'plan_id', 'id');
    }

    public function sessions()
    {
        return $this->belongsToMany(
            Sessions::class,
            'session_vrp_plan',
            'vrp_plan_id',
            'session_id'
        )->withPivot('vrp_plan_status') ;
    }

    public function CDTCountry()
    {
        return $this->hasOne('App\Models\Vrp\VrpCountryMapping', 'vrp_country_name', 'holder_country');
    }

    public function CDTCompany()
    {
        return $this->hasOne('App\Models\Company', 'plan_number', 'plan_number');
    }
    
    protected static function boot()
    {
        parent::boot();
        static::updating(function ($plan) {
            if ($plan->primary_smff !== $plan->getOriginal('primary_smff')) {
                ProviderChanges::create(
                    [
                        'plan_id' => $plan->id,
                        'old_provider' => $plan->getOriginal('primary_smff'),
                        'new_provider' => $plan->primary_smff,
                        'old_status' => $plan->getOriginal('status'),
                        'created_at' => $plan->created_at
                    ]
                );
            }

            if ($plan->qi !== $plan->getOriginal('qi')) {
                QIProviderChanges::create(
                    [
                        'plan_id' => $plan->id,
                        'old_provider' => $plan->getOriginal('qi'),
                        'new_provider' => $plan->qi
                    ]
                );
            }
        });
    }

    protected static function newAuthorized($firstDate) {
        return self::whereNotIn('plan_type', ['SOPEP','SMPEP'])
            ->withCount('Vessels')
            ->where('status', 'Authorized')
            ->orderBy('status', 'asc')
            ->where('vrp_deleted', 0)
            ->where('approval_date', '>', $firstDate)->get();
    }

    protected static function providerChangesData($firstDate) {
        $nullOld = self::where('status', 'Authorized')
            ->whereNotNull('primary_smff')
            ->whereNull('old_primary_smff')
            ->whereNotIn('plan_type', ['SOPEP','SMPEP']);
        $nullNew = self::where('status', 'Authorized')
            ->whereNull('primary_smff')
            ->whereNotNull('old_primary_smff')
            ->whereNotIn('plan_type', ['SOPEP','SMPEP']);
        return self::whereRaw('primary_smff <> old_primary_smff')
            ->where('status', 'Authorized')
            ->union($nullOld)
            ->union($nullNew)
            ->whereNotIn('plan_type', ['SOPEP','SMPEP'])->get();
    }
}
