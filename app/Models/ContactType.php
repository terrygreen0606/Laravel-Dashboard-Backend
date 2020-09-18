<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactType extends Model
{
    protected $table = 'contact_types';
    protected $guarded = [];

    public function companyContacts()
    {
        $this->belongsToMany(CompanyContact::class, 'contact_has_types');
    }
}
