<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationalStatusHistory extends Model
{
    protected $connection = 'focus_reporting';

    protected $table = 'dbo.OPS_OPERATIONAL_STATUS_HISTORY';
    protected $primaryKey = 'ID';
    public $incrementing = true;
    protected $keyType = 'int';
    const CREATED_AT = 'CREATED_AT';
    const UPDATED_AT = 'UPDATED_AT';
    protected $fillable = ['REPORT_DATE','SHIFT_NO','HOUR_START','HOUR_END','VERSION','STATUS','PAYLOAD_JSON','BASE_HISTORY_ID','CREATED_BY'];
    protected $casts = ['ID'=>'integer','REPORT_DATE'=>'date','SHIFT_NO'=>'integer','VERSION'=>'integer','BASE_HISTORY_ID'=>'integer'];
}
