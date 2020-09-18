<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    //
    protected $table = 'alert_info';
    protected $primaryKey = 'id';

    protected $fillable = [
        'contents',
        'categories',
        'active',
        'created_at',
        'updated_at',
    ];
}
