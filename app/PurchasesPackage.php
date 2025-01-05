<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchasesPackage extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'purchased_packages';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
    protected $uuidFieldName = 'purchased_packages_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'package_id',
        'customer_id',
    ];

    public function purchase() {
        return $this->hasOne('\App\Purchases', 'purchased_package_id', 'id');
    }

    public function package() {
        return $this->hasOne('\App\Package', 'id', 'package_id');
    }

    public function appointments() {

        return $this->hasOne('\App\Appointment', 'purchased_package_uuid', 'purchased_packages_uuid');
    }

    public function AllAppointments() {

        return $this->hasMany('\App\Appointment', 'purchased_package_uuid', 'purchased_packages_uuid');
    }

    public function classBooking() {

        return $this->hasOne('\App\ClassBooking', 'purchased_package_uuid', 'purchased_packages_uuid');
    }

    public function AllClassBookings() {

        return $this->hasMany('\App\ClassBooking', 'purchased_package_uuid', 'purchased_packages_uuid');
    }

    public static function createPackagePurchase($params) {
        $result = PurchasesPackage::create($params);
        return ($result) ? $result->toArray()['purchased_packages_uuid'] : null;
    }

    public static function getPurchasedPackages() {
        $packages = \App\PurchasesPackage::with('AllAppointments')
                ->get();
        return !empty($packages) ? $packages->toArray() : [];
    }

}
