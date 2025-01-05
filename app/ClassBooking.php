<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use DB;

class ClassBooking extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'class_bookings';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'class_booking_uuid';
    public $timestamps = true;
    protected $fillable = [
        'class_booking_uuid',
        'class_id',
        'class_schedule_id',
        'customer_id',
        'package_id',
        'purchased_package_uuid',
        'promocode_id',
        'actual_price',
        'discount_amount',
        'discounted_price',
        'travelling_charges',
        'paid_amount',
        'package_paid_amount',
        'transaction_id',
        'currency',
        'status',
        'total_session',
        'session_number',
        'payment_status',
        'created_at',
        'updated_at',
    ];

    /*
      In this function last param multiple is when its false then first() is work other wise get() method work.
     */

    public function customer() {
        return $this->hasOne('\App\Customer', 'id', 'customer_id');
    }

    public function purchasedPackage() {
        return $this->hasOne('\App\PurchasesPackage', 'purchased_packages_uuid', 'purchased_package_uuid');
    }

    public function classObject() {
        return $this->hasOne('\App\Classes', 'id', 'class_id');
    }

    public function schedule() {
        return $this->hasOne('\App\ClassSchedule', 'id', 'class_schedule_id');
    }

    public function package() {
        return $this->hasOne('\App\Package', 'id', 'package_id');
    }

    public function purchase() {

        return $this->hasOne('\App\Purchases', 'class_booking_id', 'id');
    }

    public function promo_code() {
        return $this->hasOne('\App\PromoCode', 'code_uuid', 'promocode_uuid');
    }

    public function transaction() {
        return $this->hasOne('\App\FreelancerTransaction', 'content_id', 'id');
    }

    public static function pluckClassBookingIds($column, $value, $pluck_data = null, $status = null) {
        $query = ClassBooking::where($column, $value)->whereNotIn('payment_status', ['failed', 'pending_payment']);
        if (!empty($status)) {
            $query = $query->where('status', '=', $status);
        }
        $result = $query->pluck($pluck_data);
        return !empty($result) ? $result->toArray() : [];
    }

    public static function pluckClassBookingIdsWithStatus($column, $value, $pluck_data = null) {
        $query = ClassBooking::where($column, $value);
        $query->where('status', '!=', "completed");
        $query->where('status', '!=', "rejected");
        $result = $query->pluck($pluck_data);
        return !empty($result) ? $result->toArray() : [];
    }

    public static function getClassBookingDetail($column, $value, $input_params = [], $multiple = false, $limit = null, $offset = null) {

        $query = ClassBooking::where($column, $value)
                ->whereNotIn('payment_status', ['pending_payment', 'failed']);
        if (!empty($input_params['class_id'])) {

            $query = $query->where('class_id', $input_params['class_id']);
        }
        if (!empty($input_params['class_schedule_id'])) {
            $query = $query->where('class_schedule_id', '=', $input_params['class_schedule_id']);
        }
        if (!empty($input_params['status']) && $input_params['status'] != 'history') {
            $query = $query->where('status', '=', $input_params['status']);
        }
        $query = $query->with('customer', 'classObject.freelancer');
        $query = $query->with(['schedule' => function ($q) use ($input_params) {
                if (!empty($input_params['status']) && $input_params['status'] != 'history') {
                    $q->where('start_date_time', '>=', strtotime(date('Y-m-d')));
                } else {
                    $q->where('start_date_time', '<=', strtotime(date('Y-m-d')));
                }
            }]);
        if ($multiple) {
            if (!empty($offset)) {
                $query = $query->offset($offset);
            }
            if (!empty($limit)) {
                $query = $query->limit($limit);
            }
            $record = $query->get();
        } else {
            $record = $query->first();
        }
        return !empty($record) ? $record->toArray() : [];
    }

    public static function createClass($inputs) {
        $result = ClassBooking::create($inputs);
        return !empty($result) ? $result->toArray() : [];
    }

    public static function classBookingCount($column, $value, $status = [], $type = null) {

        $query = ClassBooking::where($column, $value);
        if (!empty($status)) {
            $query = $query->whereIn('status', $status);
        }
        if (!empty($type)) {
            if ($type == 'upcoming') {
                $query = $query->whereHas('schedule', function ($q) {
                    $q->where(function ($inner_sql) {
                        //$inner_sql->where('class_date', '=', date('Y-m-d'));
                        $inner_sql->where('start_date_time', '>=', strtotime(date('Y-m-d')));
                        $inner_sql->where('start_date_time', '>', strtotime(date('H:i:s')));
                        //$inner_sql->where('from_time', '>', date('H:i:s'));
                        // $inner_sql->where('start_date_time', '>', date('H:i:s'));
                    });

//                    $q->orwhere(function ($inner_sql) {
//                        //$inner_sql->where('class_date', '>', date('Y-m-d'));
//                        //$inner_sql->where('start_date_time', '=', date('Y-m-d'));
//                        $inner_sql->where('start_date_time', '>', strtotime(date('H:i:s')));
//                    });
                });
            } elseif ($type == 'history') {
                $query = $query->whereHas('schedule', function ($q) {
                    $q->where(function ($inner_sql) {
                        //$inner_sql->where('class_date', '=', date('Y-m-d'));
                        $inner_sql->where('start_date_time', '<=', strtotime(date('Y-m-d')));
                        $inner_sql->where('start_date_time', '<', strtotime(date('H:i:s')));
                        //$inner_sql->where('from_time', '<', date('H:i:s'));
                        //$inner_sql->where('start_date_time', '<', date('H:i:s'));
                    });

//                    $q->orwhere(function ($inner_sql) {
//                        //$inner_sql->where('class_date', '=', date('Y-m-d'));
//                        //$inner_sql->where('start_date_time', '<', date('Y-m-d'));
//                        $inner_sql->where('start_date_time', '<', strtotime(date('H:i:s')));
//
//                    });
                });
            }
        }
//        if (!empty($type)) {
//            if ($type == 'upcoming') {
//                $query = $query->where('created_at', '>=', date("Y-m-d H:i:s"));
//            } elseif ($type == 'past') {
//                $query = $query->where('created_at', '<=', date("Y-m-d H:i:s"));
//            }
//        }
        return $query->count();
    }

    public static function pluckPackageBookingIds($package_uuid, $customer_uuid, $pluck_data = null) {
        $query = ClassBooking::where('package_uuid', '=', $package_uuid)->where('customer_uuid', '=', $customer_uuid);
        $result = $query->pluck($pluck_data);
        return !empty($result) ? $result->toArray() : [];
    }

    public static function getClassesBookingList($column, $class_ids = [], $type = 'all', $search_params = [], $limit = null, $offset = null) {
        $query = ClassBooking::whereIn($column, $class_ids);
        $query->with('classObject.freelancer', 'customer', 'schedule');
        if ($type == 'all') {
            if (!empty($offset)) {
                $query = $query->offset($offset);
            }
            if (!empty($limit)) {
                $query = $query->limit($limit);
            }
            $record = $query->get();
        } else {
            $record = $query->first();
        }
        return !empty($record) ? $record->toArray() : [];
    }

    public static function getClassesBookingIds($column, $class_ids = [], $type = 'all', $search_params = [], $limit = null, $offset = null) {
        $query = ClassBooking::whereIn($column, $class_ids);
        $query->with('classObject.freelancer', 'customer', 'schedule');
        if ($type == 'all') {
            if (!empty($offset)) {
                $query = $query->offset($offset);
            }
            if (!empty($limit)) {
                $query = $query->limit($limit);
            }
            $record = $query->pluck('id');
        } else {
            $record = $query->first();
        }
        return !empty($record) ? $record->toArray() : [];
    }

    public static function getCustomerClasses($column, $value, $type = 'all', $search_params = [], $limit = null, $offset = null) {
        $query = ClassBooking::where($column, $value);
        $query->with('classObject.freelancer', 'customer', 'schedule');
        if ($type == 'all') {
            if (!empty($offset)) {
                $query = $query->offset($offset);
            }
            if (!empty($limit)) {
                $query = $query->limit($limit);
            }
            $record = $query->get();
        } else {
            $record = $query->first();
        }
        return !empty($record) ? $record->toArray() : [];
    }

    public static function getAllClasses($column, $value, $input_params = [], $multiple = false, $limit = null, $offset = null) {
        $query = ClassBooking::where($column, $value);
        if (!empty($input_params['status'])) {
            $query = $query->where('status', '=', $input_params['status']);
        }
        $query = $query->with('customer', 'classObject.freelancer', 'schedule');
        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $record = $query->get();
        return !empty($record) ? $record->toArray() : [];
    }

    public static function getCustomerClassesWRTFreelancer($customer_uuid, $freelancer_uuid) {
        $query = ClassBooking::where('customer_id', '=', $customer_uuid)
                ->where('status', '=', 'pending')
                ->whereHas('classObject', function ($q) use ($freelancer_uuid) {
                    $q->where('freelancer_id', '=', $freelancer_uuid);
                })
                ->with('classObject')
                ->get();
        $result = self::getResult($query);
        if (empty($result)) {
            $query = ClassBooking::where('customer_id', '=', $customer_uuid)
                    ->where('status', '=', 'confirmed')
                    ->whereHas('classObject', function ($q) use ($freelancer_uuid) {
                        $q->where('freelancer_id', '=', $freelancer_uuid);
                    })
                    ->select('class_schedule_id', 'status')
                    ->get();
            $result = self::getResult($query);
        }
        return !empty($result) ? $result : [];
    }

    public static function getCompletedClassesWRTFreelancer($customer_uuid, $freelancer_uuid) {

        $query = ClassBooking::where('customer_id', '=', $customer_uuid)
                ->where('status', '=', 'completed')
                ->whereHas('classObject', function ($q) use ($freelancer_uuid) {
                    $q->where('freelancer_id', '=', $freelancer_uuid);
                })
                ->select('class_schedule_id', 'status')
                ->get();
        return $query ? $query->toArray() : [];
    }

    public static function getResult($result) {
        return $result ? $result->toArray() : [];
    }

    public static function updateClassBooking($class_schedule_uuid, $customer_uuid, $data = []) {
        $query = ClassBooking::where("class_schedule_id", '=', $class_schedule_uuid)
                ->where('customer_id', '=', $customer_uuid)
                ->update($data);
        return $query ? true : false;
    }

    public static function getClassBookingStatus($class_schedule_uuid, $customer_uuid) {

        return ClassBooking::where("class_schedule_id", '=', $class_schedule_uuid)
                        ->where('customer_id', '=', $customer_uuid)
                        ->where('status', '=', 'confirmed')
                        ->exists();
    }

    public static function getClassBookingDetailforEmail($column, $value, $input_params = []) {

        $query = ClassBooking::where($column, $value);

        if (!empty($input_params['class_id'])) {
            $query = $query->where('class_id', $input_params['class_id']);
        }

        if (!empty($input_params['class_schedule_id'])) {
            $query = $query->where('class_schedule_id', '=', $input_params['class_schedule_id']);
        }

        $query = $query->with('customer', 'classObject.freelancer');

        $query = $query->with(['schedule' => function ($q) use ($input_params) {
                if (!empty($input_params['status']) && $input_params['status'] != 'history') {
                    $q->where('start_date_time', '>=', strtotime(date('Y-m-d')));
                } else {
                    $q->where('start_date_time', '<=', strtotime(date('Y-m-d')));
                }
            }]);
        $record = $query->first();

        return !empty($record) ? $record->toArray() : [];
    }

    public static function getParticularClassBooking($class_uuid, $class_schedule_uuid, $status) {

        $query = ClassBooking::where("class_id", $class_uuid);
        if (!empty($class_schedule_uuid)) {
            $query = $query->where('class_schedule_id', '=', $class_schedule_uuid);
        }
        $query = $query->with(['schedule' => function ($q) use ($status) {
                if (!empty($status) && $status != 'history') {
                    $q->where('class_date', '>=', date('Y-m-d'));
                } else {
                    $q->where('class_date', '<=', date('Y-m-d'));
                }
            }]);
        $query = $query->with('customer', 'classObject.freelancer');
        $record = $query->first();

        return !empty($record) ? $record->toArray() : [];
    }

    public static function getClassBooking($class_schedule_uuid, $customer_uuid, $data = []) {
        $query = ClassBooking::where("class_schedule_id", '=', $class_schedule_uuid)
                ->where('customer_id', '=', $customer_uuid)
                ->with('transaction')
                ->with('purchase.purchasesTransition')
                ->with('schedule')
                ->where('status', '=', 'confirmed')
                ->first();
        return $query ? $query->toArray() : [];
    }

    public static function getClassBookings($col, $val) {
        $query = ClassBooking::where($col, '=', $val)
                ->get();
        return $query ? $query->toArray() : [];
    }

    public static function updateClassBookings($col, $val, $data = []) {
        if (ClassBooking::where($col, '=', $val)->exists()) {
            $query = ClassBooking::where($col, '=', $val)->update($data);
        } else {
            $query = true;
        }
        return (!$query) ? false : true;
    }

    protected function getPastConfirmedClassBookings() {
        //$time = Carbon::parse('-24 hours');
        $time = strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' -1 day')));

        $query = DB::table('class_bookings as u')
                ->join('class_schedules as p', 'p.id', '=', 'u.class_schedule_id')
                //->selectRaw('CONCAT(p.class_date, " ", p.from_time) as date,class_booking_uuid,u.status as status, u.class_schedule_id')
                ->selectRaw('p.start_date_time as date,class_booking_uuid,u.status as status, u.class_schedule_id,u.id')
                ->having('date', '<', $time)
                ->having('status', '=', "confirmed")
                ->orderBy('date');
        //                ->where('created_at', '<', Carbon::parse('-24 hours'));
//        $query = ClassBooking::where($column, '=', $value)
//                ->where('status', '=', $status)
////                ->whereHas('schedule', function ($query) {
////                    $query->where('class_time', '<', Carbon::parse('-24 hours'));
////                })
//                ->with(['schedule' => function ($query) {
//                        $query->select(DB::raw("CONCAT(class_date,' ',from_time) as date"));
////                    ->where('date', '<', Carbon::parse('-24 hours'));
//                    }])
//                ->with('schedule');
//                ->where('created_at', '<', Carbon::parse('-24 hours'));
//        $query = $query->where('date', '<', $time);

        $result = $query->get();

//        $result = $query->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getPastCancelledClassBookings() {
//        $time = Carbon::parse('-24 hours');
//        $query = DB::table('class_bookings as u')
//                ->join('class_schedules as p', 'p.id', '=', 'u.class_schedule_id')
//                ->selectRaw('CONCAT(p.class_date, " ", p.from_time) as date,class_booking_uuid,u.status as status')
//                ->having('date', '<', $time)
//                ->having('status', '=', "cancelled")
//                ->orderBy('date');
//        $result = $query->get();

        $time = strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' -1 day')));
        $query = DB::table('class_bookings as u')
                ->join('class_schedules as p', 'p.id', '=', 'u.class_schedule_id')
                //->selectRaw('CONCAT(p.class_date, " ", p.from_time) as date,class_booking_uuid,u.status as status, u.class_schedule_id')
                ->selectRaw('p.start_date_time as date,class_booking_uuid,u.status as status, u.class_schedule_id,u.id')
                ->having('date', '<', $time)
                ->having('status', '=', "cancelled")
                ->orderBy('date');
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function updateBookingWithIds($col, $ids = [], $data = []) {
        $query = ClassBooking::whereIn($col, $ids)
                ->update($data);
        return $query ? true : false;
    }

    public static function clientClassBookingHistoryCount($customer_uuid, $freelancer_uuid) {
        $query = ClassBooking::where('customer_uuid', '=', $customer_uuid);
//        $query = ClassBooking::where('freelancer_uuid', '=', $freelancer_uuid);
        $query = $query->whereHas('schedule', function ($q) {
            $q->where(function ($inner_sql) {
                $inner_sql->where('class_date', '=', date('Y-m-d'));
                $inner_sql->where('from_time', '<', date('H:i:s'));
            });
            $q->orwhere(function ($inner_sql) {
                $inner_sql->where('class_date', '<', date('Y-m-d'));
            });
        });
        $query = $query->whereHas('classObject', function ($q) use ($freelancer_uuid) {
            $q->where('freelancer_uuid', '=', $freelancer_uuid);
        });
        return $query->count();
    }

    public static function getFavIds($column, $ids = [], $pluck_data = null) {
        $query = ClassBooking::whereIn($column, $ids);
        $result = $query->pluck($pluck_data);
        return !empty($result) ? $result->toArray() : [];
    }

    public static function getBookingWithPurchase($bookingId) {
        $record = ClassBooking::where('id', $bookingId)->with(['purchase', 'schedule'])->get();
        return ($record) ? $record->toArray() : null;
    }

    public static function getClassPackageBookingWithPurchase($bookingId) {
        $record = ClassBooking::where('id', $bookingId)->with(['purchasedPackage.purchase', 'schedule'])->get();
        return ($record) ? $record->toArray() : null;
    }

    public static function getPackageClass($purchased_package_uuid) {
        $records = ClassBooking::where('purchased_package_uuid', $purchased_package_uuid)->get();
        return ($records) ? $records->toArray() : null;
    }

    public static function getSingleBooking($col, $val) {
        $booking = ClassBooking::where($col, $val)
                ->where('is_archive', 0)
                ->with('classObject')
                ->with('schedule')
                ->first();
        return !empty($booking) ? $booking->toArray() : [];
    }

}
