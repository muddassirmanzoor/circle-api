<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PackageClass extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'package_classes';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'package_class_uuid';
    public $timestamps = true;
    protected $fillable = [
        'package_class_uuid',
        'package_uuid',
        'class_uuid',
        'service_uuid',
        'no_of_class',
        'is_archive',
    ];
    public function Classes() {
        return $this->hasOne('\App\Classes', 'class_uuid', 'class_uuid');
    }

    public function FreelancerCategory() {
        return $this->hasOne('\App\FreelanceCategory', 'freelancer_category_uuid', 'service_uuid');
    }

    
    protected function savePackageClass($data) {
        return PackageClass::insert($data);
    }

}
