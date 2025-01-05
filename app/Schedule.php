<?php

namespace App;

use App\Helpers\CommonHelper;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'schedules';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'schedule_uuid';
    public $timestamps = true;
    protected $fillable = [
        'schedule_uuid',
        'freelancer_id',
        'day',
        'from_time',
        'to_time',
        'saved_timezone',
        'local_timezone',
        'is_archive'
    ];

    protected function saveSchedule($data)
    {
        $schedule = Schedule::insert($data);
        return $schedule;
    }

    protected function getFreelancerSchedule($column, $value)
    {
        $result = Schedule::where($column, '=', $value)->where('is_archive', 0)->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function checkFreelancerSchedule($column, $value)
    {
        $result = Schedule::where($column, '=', $value)->where('is_archive', 0)->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function deleteFreelancerSchedule($freelancer_uuid, $days_array = [])
    {
        Schedule::where('freelancer_id', '=', $freelancer_uuid)->whereIn('day', $days_array)->delete();
        /*$result = true;
        if (!empty($days_array)) {
            $query = Schedule::where('freelancer_id', '=', $freelancer_uuid)->whereIn('day', $days_array)->get();
            $result = $query;
        }
        return $result;*/
    }

    protected function deleteSchedule($col, $val)
    {
        $query = Schedule::where($col, '=', $val);
        $result = $query->delete();
        return ($result) ? true : false;
    }

    protected function getFreelancerScheduleByDay($search_params)
    {
        $day = \Carbon\Carbon::parse($search_params['date'])->format('l');

        $freelaceId = CommonHelper::getFreelancerIdByUuid($search_params['freelancer_uuid']);
        $result = Schedule::where(['freelancer_id' => $freelaceId])
            ->where('day', '=', $day)
            ->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected static function scheduleAlreadyExists($freelancerid, $day, $from_time, $to_time)
    {
        return Schedule::where('freelancer_id', $freelancerid)
            ->where('day', $day)
            ->where('from_time', $from_time)
            ->where('to_time', $to_time)
            ->count() > 0;
    }
}
