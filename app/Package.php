<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

//use DB;

class Package extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'packages';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'package_uuid';
    public $timestamps = true;
    protected $fillable = [
        'package_uuid',
        'freelancer_id',
        'package_name',
        'package_type',
        'no_of_session',
        'discount_type',
        'discount_amount',
        'currency',
        'price',
        'discounted_price',
        'package_image',
        'package_validity',
        'validity_type',
        'package_description',
        'description_video',
        'description_video_thumbnail',
        'is_archive'
    ];

    public function PackageService() {
        return $this->hasMany('\App\PackageService', 'package_id', 'id');
    }

    public function PackageAppointment() {
        return $this->hasMany('\App\Appointment', 'package_id', 'id');
    }

    public function ClassBooking() {
        return $this->hasMany('\App\ClassBooking', 'package_id', 'id');
    }

    public function PackageClass() {
        return $this->hasMany('\App\PackageClass', 'package_id', 'id');
    }

    public function classes() {
        return $this->hasMany('\App\Classes', 'freelancer_id', 'freelancer_id');
    }

    public function freelancer() {
        return $this->hasOne('\App\Freelancer', 'id', 'freelancer_id');
    }

    protected function savePackage($data) {
        $result = Package::create($data);
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getAllPackages($column, $value, $limit = null, $offset = null) {
        $query = Package::where($column, '=', $value)->where('is_archive', '=', 0);
        $query = $query->with('PackageService.FreelancerCategory.SubCategory');
        $query = $query->with('PackageClass.Classes.freelancer');
        $query = $query->with('PackageClass.Classes.singleSchedule');
        $query = $query->with('PackageClass.Classes.FreelanceCategory.SubCategory');
        $query = $query->with('Freelancer');
        $query = $query->orderBy('id', 'desc');
        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getSinglePackage($column, $value) {
        
        $query = Package::where($column, '=', $value)->where('is_archive', 0);
        $query = $query->with('PackageService.FreelancerCategory.SubCategory');
        $query = $query->with('PackageClass.Classes.freelancer');
        $query = $query->with('PackageClass.Classes.singleSchedule');
        $query = $query->with('PackageClass.Classes.FreelanceCategory.SubCategory');
        $result = $query->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getPackagesWithType($column, $value, $type = null, $current_date = null) {
        $query = Package::where($column, '=', $value);
        $query = $query->where('is_archive', 0);
        $query = $query->where('package_type', '=', $type);
//        $query->orderBy('id', 'desc');
        $query = $query->with('PackageService.FreelancerCategory.SubCategory');
        //$query = $query->with('PackageClass.Classes.freelancer');
        //$query = $query->with('PackageClass.Classes.singleSchedule');
        //$query = $query->with('PackageClass.Classes.FreelanceCategory.SubCategory');
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getPackagesCount($column, $value) {
        return Package::where($column, '=', $value)->count();
    }

    protected function getPackageDetail($column, $value, $search_params = [], $limit = null, $offset = null) {
        $query = Package::where($column, '=', $value);
        $query->with('classes.schedule.classBookings.customer');
//        $query->with(['classes' => function ($qry) use ($search_params) {
////                $qry->where('service_uuid', $search_params['service_uuid']);
//                if (!empty($search_params['date'])) {
//                    $qry = $qry->whereHas('schedule', function($q) use ($search_params) {
//                                $q->where('class_date', '=', $search_params['date']);
//                            });
//                    $qry = $qry->with(['schedule' => function ($sch_qry) use ($search_params) {
//                            $sch_qry->where('class_date', '=', $search_params['date']);
//                            $sch_qry->orderBy('from_time', 'ASC');
//                        }]);
//                }
//                $qry->with('FreelanceCategory');
//                $qry->where('is_archive', 0);
//            }]);
        $query->with('freelancer');
        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $result = $query->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getPackageDetailWithUpcomingClasses($column, $value, $search_params = [], $limit = null, $offset = null) {
        $query = Package::where($column, '=', $value);
        $query->with('classes.schedule.classBookings.customer');
        $query->with(['classes' => function ($qry) use ($search_params) {
//                $qry->where('service_uuid', $search_params['service_uuid']);
                $qry = $qry->whereHas('schedule', function ($q) {
                    $q->where('class_date', '>=', date("Y-m-d"));
                });
                $qry = $qry->with(['schedule' => function ($sch_qry) {
                        $sch_qry->where('class_date', '>=', date("Y-m-d"));
                        $sch_qry->orderBy('from_time', 'ASC');
                    }]);

                $qry->with('FreelanceCategory');
                $qry->where('is_archive', 0);
            }]);
        $query->with('freelancer');
        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $result = $query->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function updatePackage($col, $val, $data) {
        return Package::where($col, '=', $val)->update($data);
    }

//    protected function getPurchasedPackages($column, $value, $limit = null, $offset = null) {
//        $query = Package::where($column, '=', $value)->where('is_archive', 0);
//        $query = $query->with('Freelancer.profession');
//        $query = $query->with('PackageAppointment.AppointmentCustomer', 'PackageAppointment.AppointmentFreelancer', 'PackageAppointment.AppointmentService', 'PackageAppointment.AppointmentFreelancer');
//        $query = $query->with('ClassBooking.customer', 'ClassBooking.classObject', 'ClassBooking.schedule.classBookings.customer');
//        $query = $query->orderBy('id', 'desc');
//        if (!empty($offset)) {
//            $query = $query->offset($offset);
//        }
//        if (!empty($limit)) {
//            $query = $query->limit($limit);
//        }
//        $result = $query->get();
//        return !empty($result) ? $result->toArray() : [];
//    }
    protected function getPurchasedPackages($column, $value, $limit = null, $offset = null) {
        $query = Package::where($column, '=', $value)->where('is_archive', 0);
        $query = $query->with(['PackageAppointment' => function ($query) {
                $query->where('status', '!=', "rejected");
                $query->where('status', '!=', "completed");
            }]);
        $query = $query->with(['ClassBooking' => function ($query) {
                $query->where('status', '!=', "rejected");
                $query->where('status', '!=', "completed");
            }]);
        $query = $query->with('Freelancer.profession');
        $query = $query->with('PackageAppointment.AppointmentCustomer', 'PackageAppointment.AppointmentFreelancer',
            'PackageAppointment.AppointmentService', 'PackageAppointment.AppointmentFreelancer');
        $query = $query->with('PackageAppointment.LastRescheduledAppointment');
        $query = $query->with('ClassBooking.customer', 'ClassBooking.classObject', 'ClassBooking.schedule.classBookings.customer');
        $query = $query->where('package_type', 'session');
        $query = $query->orderBy('id', 'desc');
        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getPackagesUsingUuids($uuid_array = [], $limit = null, $offset = null) {
        $query = Package::whereIn('id', $uuid_array);
        $query = $query->with('Freelancer.profession');
        $query = $query->with('PackageAppointment.AppointmentCustomer', 'PackageAppointment.AppointmentFreelancer', 'PackageAppointment.AppointmentService', 'PackageAppointment.AppointmentFreelancer');
        $query = $query->with('ClassBooking.customer', 'ClassBooking.classObject', 'ClassBooking.schedule.classBookings.customer');
        $query = $query->orderBy('id', 'desc');
        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getPurchasedPackageDetails($column, $value, $query_data = []) {
        $query = Package::where($column, '=', $value);
//        $query = $query->whereHas('PackageAppointment', function($q) use ($query_data) {
//            if (!empty($query_data['customer_uuid'])) {
//                $q->where(function ($inner_sql_qr) use($query_data) {
//                    $inner_sql_q->where('customer_uuid', '=', $query_data['customer_uuid']);
//                });
//            }
//        });
//        $query = $query->whereHas('ClassBooking', function($q) use ($query_data) {
//            if (!empty($query_data['customer_uuid'])) {
//                $q->where(function ($inner_sql_qr) use($query_data) {
//                    $inner_sql_q->where('customer_uuid', '=', $query_data['customer_uuid']);
//                });
//            }
//        });
        $query = $query->with('Freelancer', 'PackageAppointment.AppointmentCustomer', 'PackageAppointment.AppointmentFreelancer', 'PackageAppointment.AppointmentService', 'PackageAppointment.AppointmentFreelancer', 'ClassBooking.customer', 'ClassBooking.classObject', 'ClassBooking.schedule.classBookings.customer');
        $query = $query->with('PackageAppointment.LastRescheduledAppointment');
        $result = $query->first();
        return !empty($result) ? $result->toArray() : [];
    }

    public static function getParticularPackageDetails($package_uuid) {
        $result = Package::where("id", '=', $package_uuid)->first();
        return !empty($result) ? $result->toArray() : [];
    }

    public static function getAllSessionPackages() {
        $query = Package::where("package_type", '=', "session")->where('is_archive', 0);
        $query = $query->with('PackageService.FreelancerCategory.SubCategory');
        $query = $query->with('PackageAppointment.AppointmentCustomer', 'PackageAppointment.AppointmentFreelancer.profession', 'PackageAppointment.AppointmentService');
        $query = $query->with('Freelancer');
        $query = $query->orderBy('id', 'desc');
        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function pluckFavIds($column, $value, $pluck_column) {
        $result = Package::where($column, '=', $value)->where('is_archive', '=', 0)->pluck($pluck_column);
        return !empty($result) ? $result->toArray() : [];
    }

    public static function getPackages($col, $val) {
        $query = Package::where($col, '=', $val);
                //->where('is_archive', '=', 0);
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function updatePackagesUsingUuids($ids, $data) {
        $query = Package::whereIn('package_uuid', $ids);
        $result = $query->update($data);
        return !empty($result) ? true : false;
    }

}
