<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Purchases extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'purchases';

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
    protected $uuidFieldName = 'purchases_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'purchase_unique_id',
        'customer_id',
        'freelancer_id',
        'purchase_datetime',
        'account_title',
        'type',
        'purchased_by',
        'purchased_in_currency',
        'service_provider_currency',
        'conversion_rate',
        'appointment_id',
        'class_booking_id',
        'purchased_package_id',
        'subscription_id',
        'customer_card_id ',
        'circl_fee',
        'transaction_charges',
        'service_amount',
        'total_amount',
        'discount',
        'discount_type',
        'total_amount_percentage',
        'tax',
        'circl_fee_percentage',
        'is_refund',
        'status',
        'is_archive',
        'purchased_premium_folders_id'
    ];

    public function subscription() {
        return $this->hasOne(Subscription::class, 'id', 'subscription_id');
    }

    public function FreelancerEarning() {
        return $this->hasMany('\App\FreelancerEarning', 'purchase_id', 'id');
    }

    public function purchasesTransition() {
        return $this->belongsTo('\App\PurchasesTransition', 'id', 'purchase_id')->orderBy('id', 'DESC');
    }

    public function appointment() {
        return $this->belongsTo('\App\Appointment', 'appointment_id', 'id');
    }
    public function premiumFolder() {
        return $this->belongsTo('\App\PurchasedPremiumFolder', 'purchased_premium_folders_id', 'id');
    }
    public function wallet() {

        return $this->belongsTo('\App\Wallet', 'id', 'purchase_id');
    }

    public function customer() {
        return $this->belongsTo('\App\Customer', 'customer_id', 'id');
    }

    public function freelancer() {
        return $this->belongsTo(Freelancer::class, 'freelancer_id', 'id');
    }

    public function AppointmentPackage() {

        return $this->belongsTo('\App\PurchasesPackage', 'purchased_package_id', 'id');
    }

    public function classbooking() {
        return $this->belongsTo('\App\ClassBooking', 'class_booking_id', 'id');
    }

    public function appointment_subscription() {
        return $this->belongsTo('\App\Subscription', 'subscription_id', 'id');
    }

    public static function createPurchase($params) {
        $result = Purchases::create($params);
        return ($result) ? $result->toArray() : null;
    }

    public static function getTransitionDetail($transitionId) {
        $result = Purchases::where('purchases_uuid', $transitionId)
                ->with([
                    'appointment.promo_code',
                    'appointment.FreelancerEarning',
                    'classbooking.classObject',
                    'classbooking.schedule',
                    'customer',
                    'freelancer.FreelancerCategories',
                    'AppointmentPackage.package',
                    'AppointmentPackage.appointments',
                    'AppointmentPackage.AllAppointments.FreelancerEarning',
                    'AppointmentPackage.classBooking.classObject',
                    'AppointmentPackage.classBooking.schedule',
                    'AppointmentPackage.AllClassBookings.classObject',
                    'AppointmentPackage.AllClassBookings.schedule',
                    'wallet',
                    'purchasesTransition',
                    'appointment_subscription.subscription_setting',
                    'FreelancerEarning',
                    'premiumFolder'
                ])
                ->first();
        return ($result) ? $result->toArray() : [];
    }

    public static function getAllTransition($col, $val, $inputs = []) {
        $login_user_type = $inputs['login_user_type'];
        $type = $inputs['type'];
        $query = Purchases::where($col, $val)->where('is_archive', 0);
        if (($type == 'pending') && ($login_user_type == 'customer')) {
            $query = $query->where('status', 'pending');
        }
        if (($type == 'completed') && ($login_user_type == 'customer')) {
            $query = $query->where('status', 'completed');
        }
        if (($type == 'all') && ($login_user_type == 'freelancer')) {
            $query = $query->whereIn('status', ['confirmed', 'succeeded', 'cancelled', 'completed', 'refunded']);
            $query = $query->whereHas('FreelancerEarning');
        }
        if (($type == 'pending') && ($login_user_type == 'freelancer')) {
            $query->where('status', '=', 'succeeded')->whereNotIn('type', ['premium_folder', 'subscription']);
        }
        if (($type == 'available') && ($login_user_type == 'freelancer')) {
            $query->whereRaw("((status = 'completed') or (status in ('completed', 'succeeded') and type in ('premium_folder', 'subscription', 'package')))");
            // $query->where(function($query) {
            //     return $query->whereIn('status', ['completed', 'succeeded'])
            //         ->whereIn('type', ['premium_folder', 'subscription']);
            // });
        }
//        $query = $query->whereHas('appointment', function ($q) {
//            $q->whereNotIn('payment_status', ['pending_payment', 'failed']);
//        });
//        $query = $query->whereHas('classbooking', function ($q) {
//            $q->whereNotIn('payment_status', ['pending_payment', 'failed']);
//        });
        $query = $query->with([
            'appointment',
            'AppointmentPackage.appointments',
            'AppointmentPackage.AllAppointments',
            'classbooking.schedule',
            'classbooking.classObject',
            'wallet', 'purchasesTransition',
            'AppointmentPackage.classBooking.classObject',
            'AppointmentPackage.AllClassBookings',
            'subscription',
            'premiumFolder'
        ]);
        if (!empty($inputs['limit'])) {
            $query = $query->limit($inputs['limit']);
        }
        if (!empty($inputs['offset'])) {
            $query = $query->offset($inputs['offset']);
        }
        $result = $query->orderBy('id', 'DESC')->get();
        return ($result) ? $result->toArray() : [];
    }

    public static function getSumOfCol($col, $val, $key) {
        $result = Purchases::where($col, $val)
                ->whereIn('status', ['succeeded'])
//                ->whereIn('status', ['pending', 'confirmed'])
//                ->whereHas('FreelancerEarning', function ($q) {
//                    $q->where('freelancer_withdrawal_id', '!=', null);
//                })
                ->sum($key);
        return ($result != 0) ? $result : 0;
    }

    public static function getPurchasesWithStatus($col, $val, $status) {
        $result = Purchases::where($col, $val)
                ->whereIn('status', [$status])
                ->get();
        return ($result) ? $result->toArray() : [];
    }

// TODO: check after demo
    protected function updatePurchaseWithIds($col, $ids = [], $data = []) {
        $query = Purchases::whereIn($col, $ids)
                ->update($data);
        return $query ? true : false;
    }

    public static function getTrasanctionByType($col, $val, $limit, $offset, $type) {
        $result = Purchases::where([$col => $val, 'status' => $type])
                ->with(['appointment', 'AppointmentPackage.appointments', 'classbooking.schedule', 'classbooking.classObject', 'wallet', 'purchasesTransition', 'AppointmentPackage.classBooking.classObject'])
                ->orderBy('id', 'DESC')
                ->get();
        return ($result) ? $result->toArray() : [];
    }

    protected function updatePurchaseData($col, $val, $data = []) {
        $query = Purchases::whereIn($col, $val)
                ->update($data);
        return $query ? true : false;
    }

    public static function getPremiumFolderPassedTransactions()
    {
        $date = new \DateTime();
        $date->modify('-1 day');
        $date = $date->format('Y-m-d H:i:s');
        $result = Purchases::where('type','premium_folder')
        ->where('status','succeeded')
        ->where('is_archive',0)
        ->where('created_at', '<=', $date)
        ->pluck('id');
        return !empty($result) ? $result->toArray() : [];
    }
}
