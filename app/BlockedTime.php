<?php

namespace App;

use App\Helpers\CommonHelper;
use Illuminate\Database\Eloquent\Model;

class BlockedTime extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'blocked_timings';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'blocked_time_uuid';
    public $timestamps = true;
    protected $fillable = [
        'blocked_time_uuid',
        'freelancer_id',
        'start_date',
        'end_date',
        'from_time',
        'to_time',
        'saved_timezone',
        'local_timezone',
        'notes',
        'is_archive'
    ];

    protected function saveSchedule($data) {
        return BlockedTime::insert($data);
    }

    protected function getBlockedTimings($column, $value, $query_date = null, $condition_date = 'end_date') {
        $query = BlockedTime::where($column, '=', $value);
        if (!empty($query_date)) {
            $query = $query->where(function($q) use ($query_date, $condition_date) {
                $q->where($condition_date, '>=', $query_date);
            });
        }
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getBlockTimeForSlots($column, $value, $query_date = null) {
        $freelanceId = CommonHelper::getFreelancerIdByUuid($value);
        $query = BlockedTime::where('is_archive', 0);
        $query->where($column, '=', $freelanceId);
        if (!empty($query_date)) {
            $query->whereRaw("'$query_date' BETWEEN start_date AND end_date");
        }
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getBlockedTimingsWithinDates($column, $value, $query_parameters = null) {
        $query = BlockedTime::where($column, '=', $value);
        if (!empty($query_parameters)) {
            $query = $query->where(function($q) use ($query_parameters) {
                $q->where('start_date', '>=', $query_parameters['start_date']);
            });
            $query = $query->where(function($qr) use ($query_parameters) {
                $qr->where('end_date', '<=', $query_parameters['end_date']);
            });
        }
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

}
