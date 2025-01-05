<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'payment_log';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'payment_log_uuid';
    public $timestamps = true;

    protected $fillable = [
        'payment_request_uuid',
        'processed_by',
        'gateway_response',
        'amount',
        'currency',
        'hyperpay_unique_id'
    ];
}
