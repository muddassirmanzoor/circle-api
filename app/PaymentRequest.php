<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentRequest extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'payment_requests';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'payment_request_uuid';
    public $timestamps = true;
    protected $fillable = [
        'user_uuid',
        'requested_amount',
        'deductions',
        'final_amount',
        'currency',
        'notes_from_freelancer'
    ];

    protected function savePaymentRequest($data) {
        $result = PaymentRequest::create($data);
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getPaymentRequestAmount($status, $freelancer_uuid) {
        $amount = self::where('user_id', $freelancer_uuid)->where('is_processed', $status)->sum('requested_amount');
        return $amount;
    }

    protected function getPaymentRequests($col, $val, $limit = 20, $offset = 0, $status = "") {

        $query = PaymentRequest::where($col, '=', $val);
        if (!empty($offset)) {
            $query = $query->offset($offset);
        } if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        if ($status == "confirmed"){
            $query = $query->where('is_processed', 2);
        } else{
            $query = $query->where('is_processed','!=', 2);
        }
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

}
