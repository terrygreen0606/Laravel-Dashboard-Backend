<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VesselNotes extends Model
{
    protected $table = 'vessel_notes';
    protected $guarded = [];

    public function creator()
    {
        return $this->belongsTo(User::class);
    }

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }
}
