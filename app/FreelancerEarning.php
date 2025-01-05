<?php

namespace App;

use App\Helpers\UuidHelper;
use Illuminate\Database\Eloquent\Model;

class FreelancerEarning extends Model {

    protected $table = 'freelancer_earnings';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'freelancer_earnings_uuid';
    public $timestamps = true;
    protected $fillable = [
        'freelancer_earnings_uuid',
        'freelancer_id',
        'earned_amount',
        'purchase_id ',
        'subscription_id',
        'purchased_package_id',
        'class_booking_id',
        'appointment_id',
        'amount_due_on',
        'currency',
        'freelancer_withdrawal_id ',
        'is_archive',
        'purchased_premium_folders_id',
    ];
    public function freelancer()
    {
        return $this->hasOne('\App\Freelancer', 'id', 'freelancer_id');
    }
    public static function createRecords($records) {
        $insertedRecords = FreelancerEarning::insert($records);
        return ($insertedRecords) ? true : false;
    }

    public static function updateData($col, $val, $data) {
        if (FreelancerEarning::where($col, $val)->exists()) {
            $updateRecord = FreelancerEarning::where($col, $val)->update($data);
            return ($updateRecord) ? true : false;
        }
        return true;
    }

    public static function getSumOfCol($col, $val, $key) {
        $result = FreelancerEarning::where($col, $val)
                ->where('freelancer_withdrawal_id', '=', null)
                ->where('is_archive', '=', 0)
                ->sum($key);
        return ($result != 0) ? $result : 0;
    }

    public static function getEarning($col, $val) {
        $result = FreelancerEarning::where($col, $val)
                ->where('is_archive', '=', 0)
                ->first();
        return !empty($result) ? $result : [];
    }

    public static function getEarningWRTTime($col, $val, $key) {
        $date = strtotime(date('Y-m-d H:i:s'));
        $query = FreelancerEarning::where($col, $val);
        $query = $query->where('freelancer_withdrawal_id', '=', null);
        $query = $query->where('is_archive', '=', 0);
        if ($key == 'pending') {
            $query = $query->where('amount_due_on', '>', $date);
        }
        if ($key == 'available') {
            $query = $query->where('amount_due_on', '<', $date)
                    ->whereIn('transfer_status', ['pending', 'completed']);
        }
        $result = $query->get();
//        $result = $query->sum('earned_amount');
        return !empty($result) ? $result->toArray() : [];
    }
   
    public function appointment()
    {
        return $this->hasOne('\App\Appointment', 'id', 'appointment_id');
    }

    public function classBook()
    {
        return $this->hasOne('\App\ClassBooking', 'id', 'class_booking_id');
    }

    public function subscription()
    {
        return $this->hasOne('\App\Subscription', 'id', 'subscription_id');
    }
    public function freelancerWithdrawl()
    {
        return $this->belongsTo('\App\FreelancerWithdrawal', 'freelancer_id', 'freelancer_id');
    }
    public function purchases()
    {
        return $this->belongsTo('\App\Purchases', 'purchase_id', 'id');
    }
    public function premiumFolder() {
        return $this->belongsTo('\App\PurchasedPremiumFolder', 'purchased_premium_folders_id', 'id');
    }
    public static function getFreelancerEarnings($col, $val , $limit = null, $offset = null,$freelancer_id=null) {
        $query = FreelancerEarning::where($col, $val)
        ->where('freelancer_id',$freelancer_id)
        ->where('is_archive', 0)
        ->with('purchases','appointment','classBook.classObject','subscription','premiumFolder.folder');
        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $query= $query->get();
        return !empty($query) ? $query->toArray() : [];
    }

}
