<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FreelancerTransaction extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'freelancer_transactions';
    protected $uuidFieldName = 'freelancer_transaction_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'freelancer_transaction_uuid',
        'transaction_id',
        'freelancer_id',
        'customer_id',
        'content_id',
        'transaction_type',
        'transaction_user',
        'transaction_date',
        'status',
        'comments',
        'payment_brand',
        'actual_amount',
        'total_amount',
//        'freelancer_currency_amount',
        'commission_rate',
        'circl_charges',
        'hyperpay_fee',
        'exchange_rate',
        'from_currency',
        'to_currency',
        'is_archive',
        'cancelled_on',
        'cancelled_by',
        'created_at',
        'updated_at'
    ];

    /*
     * All model relations goes down here
     *
     */

    public function appointment() {
        return $this->hasOne('\App\Appointment', 'appointment_uuid', 'content_uuid');
    }

    public function customer() {
        return $this->hasOne('\App\Customer', 'customer_uuid', 'customer_uuid');
    }

    public function freelancer() {
        return $this->hasOne('\App\Freelancer', 'freelancer_uuid', 'freelancer_uuid');
    }

    public function classBook() {
        return $this->hasOne('\App\ClassBooking', 'class_booking_uuid', 'content_uuid');
    }

    public function subscription() {
        return $this->hasOne('\App\Subscription', 'subscription_uuid', 'content_uuid');
    }

    public function payment_due() {
        return $this->hasMany('\App\PaymentDue', 'freelancer_transaction_uuid', 'freelancer_transaction_uuid');
    }

    public function refund_transaction() {
        return $this->hasOne('\App\RefundTransaction', 'freelancer_transaction_uuid', 'freelancer_transaction_uuid');
    }

    public function walk_in_customer(){
        return $this->hasOne('\App\WalkinCustomer', 'walkin_customer_uuid', 'customer_uuid');
    }

    protected function saveTransaction($data) {
        return FreelancerTransaction::create($data);
    }

//    protected function getTransactionDetail($column, $value) {
//        $query = FreelancerTransaction::where($column, '=', $value);
//        $query->with('appointment.package', 'appointment.promo_code', 'freelancer', 'customer', 'payment_due', 'refund_transaction');
//        $query->with('classBook.schedule', 'classBook.promo_code', 'classBook.classObject', 'classBook.package');
//        $query->with('subscription.subscription_setting');
//        $result = $query->first();
//        return !empty($result) ? $result->toArray() : [];
//    }
    protected function getTransactionDetail($column, $value) {
        $query = FreelancerTransaction::where($column, '=', $value);
        $query->with('appointment.package', 'appointment.promo_code', 'freelancer', 'customer', 'payment_due', 'refund_transaction');
        $query->with('classBook.schedule', 'classBook.promo_code', 'classBook.classObject', 'classBook.package');
        $query->with('subscription.subscription_setting');
        $result = $query->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getAllTransactions($column, $value, $inputs) {

        $query = FreelancerTransaction::where($column, '=', $value);
        $query->with('appointment', 'freelancer', 'customer', 'payment_due', 'refund_transaction');
        $query->with('classBook.schedule', 'classBook.classObject');
        $query->with('subscription.subscription_setting')->doesntHave('walk_in_customer');

        $query->where(function ($q) use($inputs){
            $q->where('status', 'confirmed');
            $q->orWhereHas('refund_transaction', function ($nstq){
                $nstq->where('refund_type', 'partial');
            });
            $q->orWhereHas('payment_due', function ($nstq){
                $nstq->where('status', 0);
            });
        });
        /*if ($inputs['login_user_type'] == 'freelancer'){
            $query->where('status', 'confirmed');
        }
        if ($inputs['login_user_type'] == 'customer'){
            $query->where('status', 'confirmed');
        }*/

        if (!empty($inputs['offset'])) {
            $query = $query->offset($inputs['offset']);
        }
        if (!empty($inputs['limit'])) {
            $query = $query->limit($inputs['limit']);
        }
        $query = $query->orderBy('created_at', 'DESC');
        $result = $query->get();

        return !empty($result) ? $result->toArray() : [];
    }

    protected function calculateEarnings($column, $value, $query_params = []) {
        $query = FreelancerTransaction::where($column, '=', $value);
        $query = $query->where('status', '=', 'confirmed');
//        if (!empty($query_params['status'])) {
//            $query = $query->where('status', '=', $query_params['status']);
//        }
        $result = $query->sum('actual_amount');
        return $result;
    }

    protected function updateParticularTransaction($column, $value, $data = []) {

        $result = FreelancerTransaction::where($column, '=', $value)->update($data);
        return (!$result) ? false : true;
    }

    protected function updateTransactions($column, $ids = [], $data = []) {
        $result = FreelancerTransaction::whereIn($column, $ids)->update($data);
        return (!$result) ? false : true;
    }

    protected function getParticularTransactions($column, $value, $search_params = []) {

        $current_date =date('Y-m-d h:i:s');
        $query = FreelancerTransaction::where($column, '=', $value);
        $query->with('appointment', 'freelancer', 'customer', 'payment_due', 'refund_transaction');
        $query->with('classBook.schedule', 'classBook.classObject');
        $query->with('subscription.subscription_setting')->doesntHave('walk_in_customer');

        /*$query->where(function ($qry) use($search_params, $current_date){
            $qry->whereHas('appointment', function ($nestquery) use($search_params, $current_date){
                if (isset($search_params['type']) && ($search_params['type'] == "pending")){
                    $nestquery->whereRaw('TIMESTAMPDIFF(MINUTE, CONCAT(appointment_date, " ", from_time), ?)< 1440', [$current_date]);
                }
                if (isset($search_params['type']) && ($search_params['type'] == "available")){
                    $nestquery->whereRaw('TIMESTAMPDIFF(MINUTE, CONCAT(appointment_date, " ", from_time), ?)>= 1440', [$current_date]);
                }
            });
            $qry->orWhereHas('classBook.schedule', function ($nestquery) use($search_params, $current_date){
                if (isset($search_params['type']) && ($search_params['type'] == "pending")){
                    $nestquery->whereRaw('TIMESTAMPDIFF(MINUTE, class_date, ?)< 1440', [$current_date]);
                }
                if (isset($search_params['type']) && ($search_params['type'] == "available")){
                    $nestquery->whereRaw('TIMESTAMPDIFF(MINUTE, class_date, ?)>= 1440', [$current_date]);
                }
            });
            if ($search_params['login_user_type'] == 'freelancer'){
                $qry->orWhereHas('payment_due', function ($nestquery) use($search_params, $current_date){
                    if (isset($search_params['type']) && ($search_params['type'] == "pending")){
                        $nestquery->whereDate('due_date', '>', $current_date);
                    }
                    if (isset($search_params['type']) && ($search_params['type'] == "available")){
                        $nestquery->whereDate('due_date', '<=', $current_date);
                    }
                });
            } else{
                $qry->orWhereHas('subscription');
            }
        });*/

        if (isset($search_params['type']) && ($search_params['type'] == "pending" || $search_params['type'] == "available")) {
            //$query = $query->where('status', 'confirmed');
            $query->where(function ($q) use($search_params){
                $q->where('status', 'confirmed');
                $q->orWhereHas('refund_transaction', function ($nstq){
                    $nstq->where('refund_type', 'partial');
                });
            });
        }
        $query = $query->orderBy('created_at', 'DESC');
        if (!empty($search_params['offset'])) {
            $query = $query->offset($search_params['offset']);
        }
        if (!empty($search_params['limit'])) {
            $query = $query->limit($search_params['limit']);
        }
        $result = $query->get();

        return !empty($result) ? $result->toArray() : [];
    }

}
