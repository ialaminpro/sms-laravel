<?php

namespace Acolyte\SmsLaravel\Models;

use Illuminate\Database\Eloquent\Model;

class SmsModel  extends Model
{
    public $timestamps = true;
    protected $table = 'sms';
    protected $primaryKey = 'id';

    protected $fillable = [
        'client_id', 'type', 'mask', 'balance', 'masking_rate', 'no_masking_rate','status', 'created_at', 'updated_at',
    ];
}
