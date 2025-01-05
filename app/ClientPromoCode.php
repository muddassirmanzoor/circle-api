<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClientPromoCode extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'client_promocodes';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'client_promocode_uuid';
    public $timestamps = true;
    protected $fillable = [
        'client_promocode_uuid',
        'freelancer_id',
        'client_id',
        'code_id',
        'coupon_code',
        'is_archive'
    ];

    protected function saveClientPromoCode($data) {
        $result = ClientPromoCode::insert($data);
        return ($result) ? $result : [];
    }


}
