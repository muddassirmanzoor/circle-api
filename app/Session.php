<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Session extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'sessions';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'session_uuid';
    public $timestamps = true;
    protected $fillable = [
        'session_uuid',
        'freelancer_uuid',
        'customer_uuid',
        'customer_name',
        'session_date',
        'from_time',
        'to_time',
        'address',
        'lat',
        'lng',
        'price',
        'status',
        'notes',
        'title',
        'is_archive'
    ];

    public function getAppliedService() {
        return $this->hasMany('App\SessionService', 'session_uuid', 'session_uuid');
    }

    protected function saveSession($data) {
        $result = Session::create($data);
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getAllSessions($column, $value) {
        $sessions = Session::where($column, '=', $value)->get();
        return !empty($sessions) ? $sessions->toArray() : [];
    }

    protected function getSessionDetail($column, $value) {
        $result = Session::where($column, '=', $value)->with('getAppliedService')->first();
        return !empty($result) ? $result->toArray() : [];
    }

}
