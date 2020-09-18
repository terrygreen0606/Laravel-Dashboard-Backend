<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackChange extends Model
{
    //
    protected $table = 'track_changes';
    protected $guarded = [];

    public function changesTableName()
    {
        return $this->belongsTo(ChangesTableName::class, 'changes_table_name_id', 'id');
    }

    public function actions()
    {
        return $this->belongsTo(Action::class, 'action_id', 'id');
    }
}
