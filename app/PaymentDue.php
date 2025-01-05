<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentDue extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'payment_due';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'payment_due_uuid';
    public $timestamps = true;
    protected $fillable = [
        'user_uuid',
        'amount',
        'currency',
        'subscription_uuid',
        'appointment_uuid',
        'class_uuid',
        'due_date',
        'is_paid',
        'payment_request_uuid',
        'payment_process_uuid',
        'freelancer_transaction_uuid',
        'status',
        'notes',
        'due_no',
        'total_dues'
    ];

    protected function savePaymentDue($data) {
        return PaymentDue::insert($data);
    }

    protected function updatePaymentDueRecords($req_uuid, $data){
        foreach ($data['payment_dues'] as $pay_due){
            self::where(['payment_due_uuid' => $pay_due, 'is_paid' => 0, 'user_uuid' => $data['logged_in_uuid']])->update(['payment_request_uuid' => $req_uuid]);
        }
    }

    protected function getUserTotalEarnings($inputs, $dueCheck = false){
        $amount_column = strtolower($inputs['to_currency']) == "pound" ? 'pound_amount' : 'sar_amount';

        $total_earnings = self::where('user_id', $inputs['freelancer_id'])->where('status', 0);
        if ($dueCheck){
            $total_earnings = $total_earnings->whereDate('due_date', '<=', date('Y-m-d'));
        }
        $total_earnings = $total_earnings->sum($amount_column);

        return $total_earnings;
    }

    protected function getUserPendingBalance($inputs){
        $amount_column = strtolower($inputs['to_currency']) == "pound" ? 'pound_amount' : 'sar_amount';
        $total_earnings = self::where('user_id', $inputs['user_id'])->where('status', 0)->whereDate('due_date', '>', date('Y-m-d'))->sum($amount_column);
        return $total_earnings;
    }
}
