<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyNotes extends Model
{
    protected $table = 'company_notes';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
