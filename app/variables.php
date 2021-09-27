<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class variables extends Model
{
    protected $fillable = [
        'usd', 'gbp', 'reporting_date',
    ];
}
