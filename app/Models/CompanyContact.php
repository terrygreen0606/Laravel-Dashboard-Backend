<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyContact extends Model
{
    protected $table = 'company_contacts';
    protected $guarded = [];

    public function companies()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function contactTypes()
    {
        return $this->belongsToMany(ContactType::class, 'contact_has_types', 'contact_id');
    }

    public function vesselsAsPrimaryPoc()
    {
        return $this->hasMany(Vessel::class, 'primary_poc_id');
    }

    public function vesselsAsSecondaryPoc()
    {
        return $this->hasMany(Vessel::class, 'secondary_poc_id');
    }
}
