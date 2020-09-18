<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChangesTableName extends Model
{
    //
    protected $table = 'changes_table_names';
    protected $guarded = [];

    public function trackChanges()
    {
        return $this->hasMany(TrackChange::class);
    }
}
