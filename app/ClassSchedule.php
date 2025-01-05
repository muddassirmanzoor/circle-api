<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClassSchedule extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'class_schedules';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'class_schedule_uuid';
    public $timestamps = true;
    protected $fillable = [
        'class_schedule_uuid',
        'class_uuid',
        'class_date',
        'from_time',
        'to_time',
        'status',
        'saved_timezone',
        'local_timezone',
        'schedule_type',
        'validity_type',
        'online_link',
        'validity',
        'is_archive'
    ];

    public function classes() {
        return $this->hasOne('\App\Classes', 'id', 'class_id');
    }

    public function classBookings() {
        return $this->hasMany('\App\ClassBooking', 'class_schedule_id', 'id')
                        ->where('is_archive', 0)
                        ->whereNotIn('payment_status', ['pending_payment', 'failed']);
    }

    public function getClassTimeAttribute() {
        return $this->class_date . " " . $this->from_time;
    }

    protected function saveClassSchedule($data) {
        return ClassSchedule::insert($data);
    }

    protected function updateClassSchedule($column, $value, $data) {
        return ClassSchedule::where($column, '=', $value)->update($data);
    }

    protected function getClassSchedule($column, $value) {
        $result = ClassSchedule::where($column, '=', $value)->with('classBookings.transaction', 'classBookings.schedule')->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getClassScheduleBasedOnUser($inputs) {
        $result = ClassSchedule::where('class_schedule_id', '=', $inputs['class_schedule_id'])
                        ->with(['classBookings' => function ($q) use ($inputs) {
                                if ($inputs['login_user_type'] == "customer") {
                                    $q->where('customer_id', $inputs['logged_in_id']);
                                }
                                $q->with('schedule', 'transaction');
                            }])->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getMultipleSchedules($ids = [], $status = null) {

        //$query = ClassSchedule::whereIn('class_schedule_id', $ids);
        $query = ClassSchedule::whereIn('id', $ids);

        if (!empty($status) && $status != 'history') {
            $query = $query->where(function ($inner_q) {
                //$inner_q->where('class_date', '>', date('Y-m-d'));
                $inner_q->where('start_date_time', '>=', strtotime(date('Y-m-d')));
                $inner_q->where('start_date_time', '>', strtotime(date('H:i:s')));
//                $inner_q->orWhere(function ($q) {
//                    //$q->where('class_date', '=', date('Y-m-d'));
//                    //$q->where('start_date_time', '=', date('Y-m-d'));
//                    //$q->where('from_time', '>', date('H:i:s'));
//                    $q->where('start_date_time', '>', strtotime(date('H:i:s')));
//                });
            });
        } elseif (!empty($status) && $status == 'history') {
            $query = $query->where(function ($inner_q) {
                // $inner_q->where('class_date', '<', date('Y-m-d'));
                $inner_q->where('start_date_time', '<=', strtotime(date('Y-m-d')));
                $inner_q->where('start_date_time', '<', strtotime(date('H:i:s')));
//                $inner_q->orWhere(function ($q) {
//                   // $q->where('class_date', '=', date('Y-m-d'));
//                   //$q->where('start_date_time', '=', date('Y-m-d'));
//                    //$q->where('from_time', '<=', date('H:i:s'));
//                    $q->where('start_date_time', '<', strtotime(date('H:i:s')));
//                });
            });
        }
        $query = $query->with('classBookings.package');
        $query = $query->with('classes.freelancer.profession');
        $query = $query->with('classes.FreelanceCategory.SubCategory');
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function deleteClassSchedule($column, $value) {
//        return ClassSchedule::where($column, '=', $value)->delete();
    }

    protected function updateSchedulesWithIds($col, $ids = [], $data = []) {
        $query = ClassSchedule::whereIn($col, $ids)
                ->update($data);
        return $query ? true : false;
    }

    public static function getClassSchedules($column, $ids = []) {
        $query = ClassSchedule::whereIn($column, $ids);
        $result = $query->select('class_date', 'from_time', 'status')
                ->get();
        return !empty($result) ? $result->toArray() : [];
    }
    protected function getPastSchdulesWithStatus($column, $value, $status, $limit = null, $offset = null) {

        $query = ClassSchedule::where($column, '=', $value);
        $query = $query->where(function ($inner_q) use ($status) {
//            $inner_q->where('appointment_start_date_time', '<=', strtotime(date('Y-m-d H:i:s', strtotime(' -24 hours'))));
            $inner_q->where('end_date_time', '<=', strtotime(date('Y-m-d H:i:s', strtotime(' -1 hour'))));
            $inner_q->where('status', '=', $status);

        });
        $result = $query->pluck('id');
        return !empty($result) ? $result->toArray() : [];
    }

}
