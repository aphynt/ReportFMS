<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OPRPlanEX extends Model
{
    //
    protected $connection = 'focus_reporting';
    protected $table = 'OPR_PLAN_EX';
    public $timestamps = false;

    protected $guarded = [];
}
