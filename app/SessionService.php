<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SessionService extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'session_services';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'session_service_uuid';
    public $timestamps = true;
    protected $fillable = [
        'session_service_uuid',
        'session_uuid',
        'service_uuid',
        'is_archive'
    ];

    protected function saveSessionService($data) {
        return SessionService::insert($data);
    }

}
