<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AppointmentService extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'appointment_services';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'appointment_service_uuid';
    public $timestamps = true;
    protected $fillable = [
        'appointment_service_uuid',
        'appointment_uuid',
        'service_uuid',
        'is_archive'
    ];

    public function FreelancerCategory() {
        return $this->hasOne('\App\FreelanceCategory', 'freelancer_category_uuid', 'service_uuid');
    }

    public function SubCategory() {
        return $this->hasOne('\App\SubCategory', 'sub_category_uuid', 'service_uuid');
    }

    protected function saveAppointmentService($data) {
        return AppointmentService::insert($data);
    }

}
