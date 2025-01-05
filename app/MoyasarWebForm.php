<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MoyasarWebForm extends Model{

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'moyasar_web_forms';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'moyasar_web_form_uuid';
    public $timestamps = true;
    protected $fillable = [
        'moyasar_web_form_uuid',
        'profile_uuid',
        'payment_id',
        'amount',
        'currency',
        'description',
        'expired_at',
        'status',
        'is_archive'
    ];
}
