<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CustomerCard extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customer_cards';

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
    protected $uuidFieldName = 'customer_card_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'customer_id',
        'card_id',
        'token',
        'card_name',
        'card_type',
        'last_digits',
        'expiry',
        'customer_checkout_id',
        'bin',
    ];

    public static function checkCardEntry($customerId,$cardId){
        return CustomerCard::where('customer_id',$customerId)->where('card_id',$cardId)->where('is_archive',0)->exists();
    }
    public static function getCustomerCard($customerId,$cardId){
        return CustomerCard::where('customer_id',$customerId)->where('card_id',$cardId)->first()->id;
    }
}
