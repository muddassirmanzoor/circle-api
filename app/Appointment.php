<?php

namespace App;

use App\Helpers\CommonHelper;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use DB;

//use DB;

class Appointment extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'appointments';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'appointment_uuid';
    public $timestamps = true;
    protected $fillable = [
        'appointment_uuid',
        'customer_id',
        'freelancer_id',
        'service_id',
        'package_id',
        'purchased_package_uuid',
        'title',
        'type',
        'status',
        'currency',
        'price',
        'appointment_start_date_time',
        'appointment_end_date_time',
        'appointment_date',
        'from_time',
        'to_time',
        'saved_timezone',
        'local_timezone',
        'transaction_id',
        'notes',
        'freelancer_notes',
        'address',
        'lat',
        'lng',
        'location_type',
        'travelling_distance',
        'total_session',
        'session_number',
        'is_archive',
        'is_online',
        'online_link',
        'discount',
        'travelling_charges',
        'discounted_price',
        'paid_amount',
        'package_paid_amount',
        'promocode_uuid',
        'has_rescheduled',
        'payment_status',
        'created_by'
    ];

    public function AppointmentService() {
        return $this->hasOne('\App\FreelanceCategory', 'id', 'service_id');
    }

    public function purchase() {

        return $this->hasOne('\App\Purchases', 'appointment_id', 'id')->orderBy('id', 'DESC');
    }

    public function purchasedPackage() {

        return $this->hasOne('\App\PurchasesPackage', 'purchased_packages_uuid', 'purchased_package_uuid');
    }

    public function purchasePackage() {
        return $this->hasOne('\App\Purchases', 'purchased_package_id', 'id');
    }

    public function AppointmentCustomer() {
        return $this->hasOne('\App\Customer', 'id', 'customer_id');
    }

    //    public function AppointmentWalkinCustomer() {
    //        return $this->hasOne('\App\WalkinCustomer', 'walkin_customer_uuid', 'customer_uuid');
    //    }

    public function AppointmentFreelancer() {
        return $this->hasOne('\App\Freelancer', 'id', 'freelancer_id');
    }

    public function review() {

        return $this->hasOne('\App\Review', 'content_id', 'id');
    }

    public function package() {
        return $this->hasOne('\App\Package', 'id', 'package_id');
    }

    public function transaction() {
        return $this->hasOne('\App\FreelancerTransaction', 'content_id', 'id');
    }

    public function promo_code() {
        return $this->hasOne('\App\PromoCode', 'code_uuid', 'promocode_uuid');
    }

    public function RescheduledAppointments() {
        return $this->hasMany('\App\RescheduledAppointment', 'appointment_id', 'id');
    }

    public function LastRescheduledAppointment() {
        return $this->hasOne('\App\RescheduledAppointment', 'appointment_id', 'id')->orderBy('created_at', 'desc');
    }

    public function FreelancerEarning() {
        return $this->hasOne('\App\FreelancerEarning', 'appointment_id', 'id')->orderBy('created_at', 'desc')->where('is_archive', 0);
    }

    protected function getFreelancerAppointments($freelancer_uuid, $type = 'all', $search_params = [], $limit = null, $offset = null) {

        $appointments = array();
        if (!empty($freelancer_uuid)) {
            $appointments = self::with('AppointmentFreelancer.profession', 'AppointmentCustomer', /*'AppointmentWalkinCustomer',*/ 'package')->where('appointments.freelancer_uuid', '=', $freelancer_uuid)->orderBy('appointments.id', 'desc');
            if (isset($search_params['status']) && !empty($search_params['status'])) {
                $appointments->where('status', $search_params['status']);
            }
            if ($type == 'first') {
                $result = $appointments->first();
            } else {
                if (!empty($offset))
                    $query = $appointments->offset($offset);
                if (!empty($limit))
                    $query = $appointments->limit($limit);
                $result = $query->get();
            }
        }
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getCustomerAppointments($customer_uuid, $type = 'all', $search_params = [], $limit = null, $offset = null) {
        $appointments = array();
        if (!empty($customer_uuid)) {
            $appointments = self::with('AppointmentFreelancer.profession', 'AppointmentCustomer', /*'AppointmentWalkinCustomer',*/ 'package')
                    ->where('appointments.customer_uuid', '=', $customer_uuid)
                    ->orderBy('appointments.id', 'desc');
            if (isset($search_params['status']) && !empty($search_params['status'])) {
                $appointments->where('status', $search_params['status']);
            }
            if ($type == 'first') {
                $result = $appointments->first();
            } else {
                if (!empty($offset))
                    $query = $appointments->offset($offset);
                if (!empty($limit))
                    $query = $appointments->limit($limit);
                $result = $query->get();
            }
        }
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getCustomerAllAppointments($column, $value, $quey_parameters = [], $limit = null, $offset = null) {
        $quey_parameters = isset($quey_parameters['search_params']) ? $quey_parameters['search_params'] : [];
        $query = Appointment::where($column, '=', $value)->whereNotIn('payment_status', ['failed', 'pending_payment']);

        if (isset($quey_parameters['appoint_type']) && $quey_parameters['appoint_type'] !== 'history') {

            if ($quey_parameters['appoint_type'] !== 'upcoming' && $quey_parameters['appoint_type'] !== 'past' && $quey_parameters['appoint_type'] !== 'history') {

                $query = $query->where('status', '=', $quey_parameters['appoint_type']);
                //                $query = $query->where('appointment_date', '>=', date('Y-m-d'));
                $query = $query->where(function ($inner_q) {
                    // $inner_q->where('appointment_date', '>', date('Y-m-d'));

                    $inner_q->where('appointment_start_date_time', '>=', strtotime(date('Y-m-d')));
                    $inner_q->where('appointment_start_date_time', '>', strtotime(date('H:i:s')));
                    //                    $inner_q->orWhere(function ($q) {
                    //                        //$q->where('appointment_date', '=', date('Y-m-d'));
                    //                       // $q->whereDate('appointment_start_date_time', '=', strtotime(date('Y-m-d')));
                    //                        $q->where('appointment_start_date_time', '>', strtotime(date('H:i:s')));
                    //                    });
                });
            } elseif ($quey_parameters['appoint_type'] === 'upcoming') {
                $query = $query->where('status', '!=', 'cancelled');
                $query = $query->where('status', '!=', 'rejected');
                //                $query = $query->where('appointment_date', '>=', date('Y-m-d'));
                $query = $query->where(function ($inner_q) {
                    //$inner_q->where('appointment_date', '>', date('Y-m-d'));
                    $inner_q->where('appointment_start_date_time', '>=', strtotime(date('Y-m-d')));
                    $inner_q->where('appointment_start_date_time', '>', strtotime(date('H:i:s')));
                    //                    $inner_q->orWhere(function ($q) {
                    //                        //$q->whereDate('appointment_start_date_time', '=', strtotime(date('Y-m-d')));
                    //                        $q->where('appointment_start_date_time', '>', strtotime(date('H:i:s')));
                    //                    });
                });
            }
        } elseif (isset($quey_parameters['appoint_type']) && ($quey_parameters['appoint_type'] === 'history' || $quey_parameters['appoint_type'] === 'past')) {
            //            $query = $query->where('appointment_date', '<=', date('Y-m-d'));
            $query = $query->where('status', '!=', 'processing');
            $query = $query->where(function ($inner_q) {
                //$inner_q->where('appointment_date', '<', date('Y-m-d'));
                $inner_q->where('appointment_start_date_time', '<=', strtotime(date('Y-m-d H:i:s')));
                //                $inner_q->orWhere(function ($q) {
                //                   // $q->whereDate('appointment_start_date_time', '=', strtotime(date('Y-m-d')));
                //                    $q->where('appointment_start_date_time', '<=', strtotime(date('H:i:s')));
                //                });
            });
        }
        if (isset($quey_parameters['date'])) {
            $query = $query->where('appointment_date', '=', $quey_parameters['date']);
        }
        if (isset($quey_parameters['from_time'])) {
            $query = $query->where('from_time', '=', $quey_parameters['from_time']);
        }
        if (isset($quey_parameters['to_time'])) {
            $query = $query->where('to_time', '=', $quey_parameters['to_time']);
        }
        $query = $query->with(
                'AppointmentService.SubCategory',
                'AppointmentFreelancer.profession',
                'AppointmentCustomer',
                /* 'AppointmentWalkinCustomer', */
                'package'
        );
        $query = $query->with('LastRescheduledAppointment');
        $query = $query->orderBy('created_at', 'DESC');
        //        if (!empty($offset)) {
        //            $query = $query->offset($offset);
        //        }
        //        if (!empty($limit)) {
        //            $query = $query->limit($limit);
        //        }
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getFreelancerAllAppointments($column, $value, $quey_parameters = [], $limit = null, $offset = null, $type = null) {


        $query = Appointment::where($column, '=', $value)
                ->whereNotIn('payment_status', ['failed', 'pending_payment']);

        if (isset($quey_parameters['status']) && $quey_parameters['status'] !== 'history') {

            $query = $query->where('status', '=', $quey_parameters['status']);
            //            $query = $query->where('appointment_date', '>=', date('Y-m-d'));
            $query = $query->where(function ($inner_q) {
                // $inner_q->where('appointment_date', '>', date('Y-m-d'));
                $inner_q->where('appointment_start_date_time', '>=', strtotime(date('Y-m-d')));
                $inner_q->where('appointment_start_date_time', '>', strtotime(date('H:i:s')));

                //                $inner_q->orWhere(function ($q) {
                //                    $q->where('appointment_date', '=', date('Y-m-d'));
                //                    $q->where('from_time', '>', date('H:i:s'));
                //
                //                });
            });
        } elseif (isset($quey_parameters['status']) && $quey_parameters['status'] === 'history') {
            $query = $query->where('status', '!=', 'pending');
            $query = $query->where('status', '!=', 'processing');
            $query = $query->where(function ($inner_q) {
                //                $inner_q->whereDate('appointment_date', '<=', date('Y-m-d'));
                //                $inner_q->whereTime('from_time', '<', date('H:i:s'));
                $inner_q->where('appointment_start_date_time', '<=', strtotime(date('Y-m-d H:i:s')));
            });
        }
        if (isset($quey_parameters['date'])) {
            $timeZone = (isset($quey_parameters['local_timezone'])) ? $quey_parameters['local_timezone'] : 'Asia/Karachi';
            $startDate = strtotime(CommonHelper::convertSinglemyDateToTimeZone($quey_parameters['date'] . ' 00:00:00', $timeZone, 'UTC'));
            $endDate = strtotime(CommonHelper::convertSinglemyDateToTimeZone($quey_parameters['date'] . ' 23:59:00', $timeZone, 'UTC'));

            $query = $query->whereBetween('appointment_start_date_time', [$startDate, $endDate])
                            ->where('status', '<>', 'cancelled')->where('status', '<>', 'rejected');
        }
        if (isset($quey_parameters['from_time'])) {
            $query = $query->where('from_time', '=', $quey_parameters['from_time']);
        }
        if (isset($quey_parameters['to_time'])) {
            $query = $query->where('to_time', '=', $quey_parameters['to_time']);
        }

        $query = $query->with(
                'AppointmentService.SubCategory',
                'AppointmentFreelancer.profession',
                'AppointmentCustomer',
                'package'
        );
        $query = $query->with('LastRescheduledAppointment');
        $query = $query->orderBy('created_at', 'DESC');
        if (empty($type)) {
            if (!empty($offset)) {
                $query = $query->offset($offset);
            }
            if (!empty($limit)) {
                $query = $query->limit($limit);
            }
        }
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getClientAppointmentsRevenue($freelancer_uuid, $customer_uuid) {
        return Appointment::where('freelancer_id', '=', $freelancer_uuid)
                        ->where('customer_id', '=', $customer_uuid)
                        ->where('status', '=', 'completed')
                        ->sum('price');
    }

    protected function getAppointmentsRevenue($column, $value) {
        return Appointment::where($column, '=', $value)
                        ->where('status', '=', 'completed')
                        ->sum('price');
    }

    protected function getClientAppointmentsCount($freelancer_uuid, $customer_uuid, $condition = null) {
        $query = Appointment::where('freelancer_id', '=', $freelancer_uuid);
        $query->where('customer_id', '=', $customer_uuid);
        $query->where('status', '!=', 'processing');
        if ($condition != null) {
            $query->where('appointment_start_date_time', $condition, strtotime(date('Y-m-d')));
        }

        return $query->count();
    }

    protected function oneDayAppointmentCheck($customer_uuid, $freelancer_uuid) {
        $data = Appointment::where('freelancer_id', '=', $freelancer_uuid)
                ->where('customer_id', '=', $customer_uuid)
                ->where('status', "pending")
                ->get();
        $result = self::getResult($data);
        if (empty($result)) {
            $data = Appointment::where('freelancer_id', '=', $freelancer_uuid)
                    ->where('customer_id', '=', $customer_uuid)
                    ->where('status', "confirmed")
                    ->select('status', 'appointment_date', 'from_time')
                    ->get();
            $result = self::getResult($data);
        }
        return !empty($result) ? $result : [];
    }

    protected function completedAppointmentCheck($customer_uuid, $freelancer_uuid) {
        $data = Appointment::where('freelancer_id', '=', $freelancer_uuid)
                ->where('customer_id', '=', $customer_uuid)
                ->where('status', "completed")
                ->select('status', 'appointment_date', 'from_time')
                ->get();
        return ($data) ? $data->toArray() : [];
    }

    protected function getResult($result) {
        return ($result) ? $result->toArray() : [];
    }

    protected function checkHasAppointment($customer_uuid, $freelancer_uuid) {
        $result = Appointment::where('freelancer_uuid', '=', $freelancer_uuid)
                ->where('customer_uuid', '=', $customer_uuid)
                ->where(function ($q) {
                    $q->where('status', "pending")
                    ->orWhere('status', "confirmed");
                })
                //                ->where('status', '=', 'pending')
                //                ->ORwhere('status', '=', 'confirmed')
                //                ->ORwhereBetween('from_time', [now()->subMinutes(1440), now()])
                ->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getClientAppointments($freelancer_uuid, $customer_uuid, $limit = null, $offset = null) {
        $query = Appointment::where('freelancer_id', '=', $freelancer_uuid);
        $query = $query->where('customer_id', '=', $customer_uuid);
        $query = $query->with(
                'AppointmentService.SubCategory',
                'AppointmentFreelancer.profession',
                'AppointmentCustomer'
                /* , 'AppointmentWalkinCustomer' */,
                'package'
        );
        $query = $query->with('LastRescheduledAppointment');
        $query = $query->where('status', '!=', 'processing');

        $query->where(function ($q) {
            $q->where(function ($q2) {
                $q2->where('status', 'pending');
                $q2->where(function ($inner_q) {
                    $inner_q->where('appointment_date', '>', date('Y-m-d'));
                    $inner_q->orWhere(function ($q3) {
                        $q3->where('appointment_date', '=', date('Y-m-d'));
                        $q3->where('from_time', '>', date('H:i:s'));
                    });
                });
            });
            $q->orWhere(function ($q4) {
                $q4->where('status', '!=', 'pending');
            });
        });

        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $result = $query->get();

        return !empty($result) ? $result->toArray() : [];
    }

    protected function getFreelancerAllAppointmentWithinDates($freelancer_uuid, $quey_parameters = []) {
        $query = Appointment::where('freelancer_id', '=', $freelancer_uuid);
        if (isset($quey_parameters['start_date'])) {
            $query = $query->where('appointment_date', '>=', $quey_parameters['start_date']);
        }
        if (isset($quey_parameters['end_date'])) {
            $query = $query->where('appointment_date', '<=', $quey_parameters['end_date']);
        }
        $query = $query->with('AppointmentFreelancer.profession', 'AppointmentCustomer', 'package');
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getUpcomingAppointments($column, $value, $limit = null, $offset = null) {

        $query = Appointment::where($column, '=', $value)->whereNotIn('payment_status', ['pending_payment', 'failed']);
        $query = $query->where(function ($inner_q) {
            //$inner_q->where('appointment_date', '>', date('Y-m-d'));
            //$inner_q->where('appointment_end_date_time', '>=', strtotime(date('Y-m-d')));
            $inner_q->where('appointment_end_date_time', '>', time());
        });
        $query = $query->with(
                'AppointmentService',
                'AppointmentFreelancer.profession',
                'AppointmentCustomer',
                //'AppointmentWalkinCustomer',
                'package'
        );
        $query = $query->with('LastRescheduledAppointment');
        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getBalance($column, $value, $condition) {
        return Appointment::where($column, '=', $value)
                        ->where('status', 'confirmed')
                        ->where('appointment_start_date_time', $condition, strtotime(date('Y-m-d H:i:s')))
                        ->sum('price');
    }

    protected function getCustomerPackageAppointments($customer_uuid = null, $package_uuid = null, $limit = null, $offset = null) {
        $query = Appointment::where('customer_uuid', '=', $customer_uuid);
        $query = Appointment::where('package_uuid', '=', $package_uuid);
        //        $query = $query->where(function ($inner_q) {
        //            $inner_q->where('appointment_date', '>', date('Y-m-d'));
        //            $inner_q->orWhere(function ($q) {
        //                $q->where('appointment_date', '=', date('Y-m-d'));
        //                $q->where('from_time', '>', date('H:i:s'));
        //            });
        //        });
        $query = $query->with('AppointmentService', 'AppointmentFreelancer.profession', 'AppointmentCustomer',/* 'AppointmentWalkinCustomer',*/ 'package');
        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getFreelancerAppointmentsCount($freelancer_uuid, $status = null) {
        $query = Appointment::where('freelancer_id', '=', $freelancer_uuid)->whereNotIn('payment_status', ['failed', 'pending_payment']);
        if (!empty($status)) {
            $query = $query->where('status', '=', $status);
            if ($status !== 'history') {
                $query = $query->where(function ($inner_q) {
                    $inner_q->where('appointment_start_date_time', '>=', strtotime(date('Y-m-d')));
                    $inner_q->where('appointment_start_date_time', '>', strtotime(date('H:i:s')));

                    //                    $inner_q->orWhere(function ($q) {
                    //                        $q->where('appointment_date', '=', date('Y-m-d'));
                    //                        $q->where('appointment_start_date_time', '>', date('H:i:s'));
                    //                    });
                });
            }
        }
        return $query->count();
    }

    protected function getCustomerAppointmentsCount($customer_uuid, $status = [], $type = 'current') {
        $query = Appointment::where('customer_id', '=', $customer_uuid)->whereNotIn('payment_status', ['failed', 'pending_payment']);
        if (!empty($status)) {
            $query = $query->whereIn('status', $status);
        }
        if ($type == 'current') {
            //$query = $query->where(function ($inner_q) {
            //$inner_q->whereDate('appointment_date', '>', date('Y-m-d'));
            $query->where('appointment_start_date_time', '>=', strtotime(date('Y-m-d')));
            $query->where('appointment_start_date_time', '>', strtotime(date('H:i:s')));
            //                $inner_q->orWhere(function ($q) {
            //                   // $q->whereDate('appointment_date', '=', date('Y-m-d'));
            //                   //$q->where('from_time', '>', date('H:i:s'));
            //                    //$q->whereDate('appointment_start_date_time', '=', strtotime(date('Y-m-d H:i:s')));
            //                    $q->where('appointment_start_date_time', '>', strtotime(date('H:i:s')));
            //                });
            //   });
        }
        if ($type == 'history') {
            $query = $query->where(function ($inner_q) {
                //$inner_q->whereDate('appointment_date', '<', date('Y-m-d'));
                $inner_q->where('appointment_start_date_time', '<=', strtotime(date('Y-m-d')));
                $inner_q->where('appointment_start_date_time', '<', strtotime(date('H:i:s')));
                //                $inner_q->orWhere(function ($q) {
                //                    //$q->whereDate('appointment_date', '=', date('Y-m-d'));
                //
                //                    //$q->where('from_time', '<', date('H:i:s'));
                //                    //$q->whereDate('appointment_start_date_time', '=', strtotime(date('Y-m-d')));
                //                    $q->where('appointment_start_date_time', '<', strtotime(date('H:i:s')));
                //                });
            });
        }
        return $query->count();
    }

    protected function getAppointmentDetail($column, $value) {
        $appointment_detail = Appointment::where($column, '=', $value)
                ->with(
                        'AppointmentService',
                        'AppointmentFreelancer.profession',
                        'AppointmentCustomer',
                        //  'AppointmentWalkinCustomer'
                        'package',
                        'transaction'
                )
                ->with('LastRescheduledAppointment')
                ->first();
        return !empty($appointment_detail) ? $appointment_detail->toArray() : [];
    }

    protected function getAppointmentWithPurchasedPackages($column, $value) {
        $appointment_detail = Appointment::where($column, '=', $value)
                ->with(
                        'AppointmentService',
                        'AppointmentFreelancer.profession',
                        'package',
                        'AppointmentCustomer',
                        //'AppointmentWalkinCustomer',
                        'transaction',
                        'LastRescheduledAppointment'
                )
                ->get();
        return !empty($appointment_detail) ? $appointment_detail->toArray() : [];
    }

    protected function saveAppointment($data) {
        $result = Appointment::create($data);
        return !empty($result) ? $result->toArray() : [];
    }

    protected function updateAppointmentStatus($column, $value, $data) {
        return Appointment::where($column, '=', $value)->update($data);
    }

    protected function searchAppointments($column, $value, $query_parameters = [], $limit = null, $offset = null) {
        //$query = new Appointment;
        $query = Appointment::where($column, '=', $value);
        if (isset($query_parameters['status'])) {
            $query = $query->where('status', '=', $query_parameters['status']);
        }
        if (isset($query_parameters['start_date'])) {
            $query = $query->where('appointment_date', '>=', $query_parameters['start_date']);
        }
        if (isset($query_parameters['end_date'])) {
            $query = $query->where('appointment_date', '<=', $query_parameters['end_date']);
        }
        if (isset($query_parameters['from_time'])) {
            $query = $query->where('from_time', '>=', $query_parameters['from_time']);
        }
        if (isset($query_parameters['to_time'])) {
            $query = $query->where('to_time', '<=', $query_parameters['to_time']);
        }
        if (isset($query_parameters['customer'])) {
            $customerId = CommonHelper::getCutomerIdByUuid($query_parameters['customer']);
            $query = $query->where('customer_id', '=', $customerId);
        }
        if (isset($query_parameters['service_uuid'])) {
            $subCategory = CommonHelper::getRecordByUuid('freelancer_categories', 'freelancer_category_uuid', $query_parameters['service_uuid'], 'id');
            $query = $query->where('service_id', '=', $subCategory);
        }
        $query = $query->with('AppointmentService', 'AppointmentFreelancer.profession', 'AppointmentCustomer'/* , 'AppointmentWalkinCustomer' */, 'package');
        $query = $query->with('LastRescheduledAppointment');

        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getAllAppointments($column, $value, $quey_parameters = [], $limit = null, $offset = null) {
        $query = Appointment::where($column, '=', $value);
        if (isset($quey_parameters['status']) && $quey_parameters['status'] != 'past') {
            $query = $query->where('status', '=', $quey_parameters['status']);
            $query = $query->where('appointment_date', '>', date('Y-m-d'));
        }
        //        if (isset($quey_parameters['date'])) {
        //            $query = $query->where('appointment_date', '=', $quey_parameters['date']);
        //        }
        //        if (!isset($quey_parameters['date']) && isset($quey_parameters['appoint_type']) && $quey_parameters['appoint_type'] == 'past') {
        //            $query = $query->where('appointment_date', '<', date('Y-m-d'));
        //        }
        //if (!isset($quey_parameters['date']) && isset($quey_parameters['appoint_type']) && $quey_parameters['appoint_type'] == 'upcoming') {
        //$query = $query->where('appointment_date', '>', date('Y-m-d'));
        //}
        //        if (isset($quey_parameters['from_time'])) {
        //            $query = $query->where('from_time', '=', $quey_parameters['from_time']);
        //        }
        //        if (isset($quey_parameters['to_time'])) {
        //            $query = $query->where('to_time', '=', $quey_parameters['to_time']);
        //        }
        $query = $query->with('AppointmentService.SubCategory', 'AppointmentFreelancer.profession', 'AppointmentCustomer', /*'AppointmentWalkinCustomer',*/ 'package');
        $query = $query->orderBy('created_at', 'DESC');
        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getFreelancerAppointmentsAmount($freelancer_uuid, $status = null) {
        $query = Appointment::where('freelancer_uuid', '=', $freelancer_uuid);
        if (!empty($status)) {
            $query = $query->where('status', '=', $status);
            dd($query->sum('price'));
        }
        return $query->sum('price');
    }

    public static function pluckFavIds($column, $value, $pluck_data = null) {
        $query = Appointment::where($column, $value);
        $result = $query->pluck($pluck_data);
        return !empty($result) ? $result->toArray() : [];
    }

    public static function pluckFavIdsWithStatus($column, $value, $pluck_data = null) {
        $query = Appointment::where($column, $value);
        $query->where('status', '!=', "completed");
        $query->where('status', '!=', "rejected");
        $result = $query->pluck($pluck_data);
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getPendingAppointments($column, $value) {
        $query = Appointment::where($column, '=', $value);
        $query = $query->where('status', '=', 'pending');
        $query = $query->where(function ($inner_q) {
            $inner_q->where('appointment_date', '>=', date('Y-m-d'));
            $inner_q->orWhere(function ($q) {
                $q->where('appointment_date', '=', date('Y-m-d'));
                $q->where('from_time', '>', date('H:i:s'));
            });
        });
        $query = $query->with('AppointmentService', 'AppointmentFreelancer.profession', 'AppointmentFreelancer.profession', 'AppointmentCustomer', /*'AppointmentWalkinCustomer',*/ 'package');
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getPendingConfirmAppointments($column, $value) {
        $query = Appointment::where($column, '=', $value);
        $query = $query->whereIn('status', ['pending', 'confirmed']);
        $query = $query->where(function ($inner_q) {
            $inner_q->where('appointment_date', '>=', date('Y-m-d'));
            $inner_q->orWhere(function ($q) {
                $q->where('appointment_date', '=', date('Y-m-d'));
                $q->where('from_time', '>', date('H:i:s'));
            });
        });
        $query = $query->with('AppointmentService', 'AppointmentFreelancer.profession', 'AppointmentFreelancer.profession', 'AppointmentCustomer', /*'AppointmentWalkinCustomer',*/ 'package');
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getPastAppointmentsWithStatus($column, $value, $status, $limit = null, $offset = null) {

        $query = Appointment::where($column, '=', $value);
        $query = $query->where(function ($inner_q) use ($status) {
//            $inner_q->where('appointment_end_date_time', '<=', strtotime(date('Y-m-d H:i:s', strtotime(' -24 hours'))));
            $inner_q->where('appointment_end_date_time', '<=', strtotime(date('Y-m-d H:i:s', strtotime(' -1 hour'))));
            //$inner_q->where('appointment_start_date_time', '<', strtotime(date('Y-m-d', strtotime(' -1 day'))));
            $inner_q->where('status', '=', $status);

            //            $inner_q->orWhere(function ($q) {
            //                $q->where('appointment_date', '=', date('Y-m-d'));
            //                $q->where('from_time', '<', date('H:i:s'));
            //            });
        });
        //        $result = $query->pluck('appointment_uuid');
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function updateAppointmentWithIds($col, $ids = [], $data = []) {
        $query = Appointment::whereIn($col, $ids)
                ->update($data);
        return $query ? true : false;
    }

    protected function getAppointmentWithIds($col, $uuid, $val, $ids = []) {
        $query = Appointment::whereIn($val, $ids)
                ->where($col, '=', $uuid)
                ->where('is_archive', '', 0)
                ->where('status', '!=', 'cancelled')
                ->where('status', '!=', 'rejected')
                ->where('status', '!=', 'completed');
        $result = $query->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getPastAppointments($column, $value, $status) {

        $query = Appointment::where($column, '=', $value)
                ->where('status', '=', $status)
                ->where('purchased_package_uuid', NULL)
                ->where('created_at', '<', Carbon::parse('-24 hours'))
                ->with('purchase.purchasesTransition')
                ->with('purchase.wallet');
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getAppointmentsToReject($column, $value, $status) {
        $time = strtotime(date('Y-m-d H:i:s'));
        $query = Appointment::where($column, '=', $value)
                ->where('purchased_package_uuid', NULL)
                ->where('status', '=', $status)
                ->where('appointment_start_date_time', '<', $time)
                ->with('purchase.purchasesTransition')
                ->with('purchase.wallet');
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getClientAppointmentsHistoryCount($customer_uuid, $freelancer_uuid, $status = []) {
        $query = Appointment::where('customer_uuid', '=', $customer_uuid);
        $query = $query->where('freelancer_uuid', '=', $freelancer_uuid);
        if (!empty($status)) {
            $query = $query->whereIn('status', $status);
        }

        $query = $query->where(function ($inner_q) {
            $inner_q->whereDate('appointment_date', '<', date('Y-m-d'));
            $inner_q->orWhere(function ($q) {
                $q->whereDate('appointment_date', '=', date('Y-m-d'));
                $q->where('to_time', '<', date('H:i:s'));
            });
        });

        return $query->count();
    }

    protected function getSingleAppointment($column, $value) {
        $appointment_detail = Appointment::where($column, '=', $value)
                ->where('is_archive', '=', 0)
                ->first();
        return !empty($appointment_detail) ? $appointment_detail->toArray() : [];
    }

    protected function getFavIdsOfFutureAppointments($col, $val, $key) {
        $query = Appointment::where('is_archive', '=', 0)
                ->where($col, '=', $val)
                ->where('status', '=', 'pending');
        $result = $query->pluck($key);
        return !empty($result) ? $result->toArray() : [];
    }

    public static function getAppointmentWithPurchase($appointment_uuid) {
        $record = Appointment::where('appointment_uuid', $appointment_uuid)
                        ->with('purchase.purchasesTransition')
                        ->with('purchase.wallet')->get();
        return ($record) ? $record->toArray() : null;
    }

    public static function getAppointmentPackageWithPurchase($appointment_uuid) {
        $record = Appointment::where('appointment_uuid', $appointment_uuid)->with('purchasedPackage.purchase')->get();
        return ($record) ? $record->toArray() : null;
    }

    public static function getPackageAppointments($col, $val) {
        $record = Appointment::where($col, $val)
                        ->where('is_archive', 0)
                        ->with('purchase.purchasesTransition')
                        ->with('purchase.wallet')->get();
        return ($record) ? $record->toArray() : null;
    }

}

//            DB::enableQueryLog(); // Enable query log
//            dd(DB::getQueryLog()); // Show results of log
