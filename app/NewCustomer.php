<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use DB;

class NewCustomer extends Authenticatable {

    use Notifiable;

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'new_customers';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'new_customer_uuid';
    public $timestamps = true;
    protected $fillable = [
        'id',
        'new_customer_uuid',
        'email',
        'phone_number',
        'ip_address',
        'user_type',
        'module'
    ];

    protected function saveCustomer($data = []) {
        $save = NewCustomer::create($data);
        return !empty($save) ? $save->toArray() : [];
    }

}
