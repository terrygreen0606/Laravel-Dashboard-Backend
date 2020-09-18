<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Port extends Model
{
    //
    protected $table = 'ports';
    protected $primaryKey = 'id';

    protected $fillable = [
      'locode_id',
      'country',
      'unlocode',
      'name',
      'name_ascii',
      'subdivision',
      'status',
      'port_function',
      'date_open',
      'iata',
      'latitude',
      'longitude',
      'description',
    ];
}
