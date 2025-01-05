<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PackageService extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'package_services';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'package_service_uuid';
    public $timestamps = true;
    protected $fillable = [
        'package_service_uuid',
        'package_id',
        'service_id',
        'no_of_session',
        'is_archive',
    ];

    public function FreelancerCategory() {
        return $this->hasOne('\App\FreelanceCategory', 'id', 'service_id');
    }

    public function FreelancerSubCategory() {
        return $this->hasOne('\App\FreelanceCategory', 'sub_category_uuid', 'service_uuid');
    }

    protected function savePackageService($data) {
        return PackageService::insert($data);
    }

    public static function updateServicesWithIds($column, $ids, $data) {
        $result = PackageService::whereIn($column, $ids)->update($data);
        return !empty($result) ? true : false;
    }

}
