<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CurrencyConversion extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'currency_conversion';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
    protected $uuidFieldName = 'currency_conversion_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'is_archived'
    ];

    protected static function createorUpdate($from, $to, $data) {
        return CurrencyConversion::updateOrCreate(['from_currency' => $from, 'to_currency' => $to,'is_archived' => 0], $data);
    }

//    public function getUpdatedAtAttribute($date)
//    {
//        return Carbon::createFromFormat('Y-m-d H:i:s', $date)->format('Y-m-d H:i:s');
//    }

    public static function getCurrency($fromCurrency,$toCurrency){
       return  CurrencyConversion::where('from_currency',$fromCurrency)->where('to_currency',$toCurrency)->first();

    }
}
