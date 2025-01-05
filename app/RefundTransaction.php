<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

//use DB;

class RefundTransaction extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'refund_transaction';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'refund_transaction_uuid';
    public $timestamps = true;
    protected $fillable = [
        'freelancer_transaction_uuid',
        'freelancer_uuid',
        'customer_uuid',
        'content_uuid',
        'transaction_type',
        'amount',
        'currency',
        'hyperpay_fee',
        'circl_charges',
        'is_archive',
        'refund_type'
    ];
}
