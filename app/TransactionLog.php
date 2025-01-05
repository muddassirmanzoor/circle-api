<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

//use DB;

class TransactionLog extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'transaction_logs';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'transaction_log_uuid';
    public $timestamps = true;
    protected $fillable = [
        'user_uuid',
        'request_params',
        'gateway_response',
        'amount',
        'currency'
    ];
}
