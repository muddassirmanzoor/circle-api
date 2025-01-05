<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchasesTransition extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'purchased_transactions';

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
    protected $uuidFieldName = 'purchases_transition_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'purchase_id',
        'currency',
        'amount',
        'transaction_type',
        'gateway_response',
        'request_parameters',
        'transaction_status',
        'checkout_transaction_id',
        'class_booking_id',
        'appointment_booking_id',
        'cko_id',
        'customer_card_id',
    ];

    public function purchase() {
        return $this->belongsTo(Purchases::class, 'purchase_id', 'id');
    }

    public function customerCards() {
        return $this->belongsTo(CustomerCard::class, 'customer_card_id', 'id');
    }

    public static function getPurchaseTransition($id) {
        $records = PurchasesTransition::where('id', $id)->with('purchase')->get();
        return ($records) ? $records->toArray() : null;
    }
    public static function getSingleTransition($id) {
        $records = PurchasesTransition::where('id', $id)->with('purchase')->first();
        return ($records) ? $records->toArray() : null;
    }

    public static function createPurchase($params) {
        $result = PurchasesTransition::create($params);
        return ($result) ? $result->toArray() : null;
    }

}
