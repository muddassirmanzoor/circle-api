<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;
use App\Customer;
use App\WalkinCustomer;
use App\Appointment;
use App\ClassBooking;
use App\ClassSchedule;

Class CustomerChecksHelper {
    /*
      |--------------------------------------------------------------------------
      | CustomerChecksHelper that contains customer related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use customer processes
      |
     */

    /**
     * Description of CustomerChecksHelper
     *
     * @author ILSA Interactive
     */
    public static function checkCustomerExistingSession($inputs = []) {

        $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
        $validation = Validator::make($inputs, CustomerChecksValidationHelper::checkCustomerAppointmentRules()['rules'], CustomerChecksValidationHelper::checkCustomerAppointmentRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return ['success' => false, 'message' => $validation->errors()->first()];
        }

        $customer = Customer::getSingleCustomerDetail('id', $inputs['customer_id']);

//        if (empty($customer)) {
//            $walkin_customer = WalkinCustomer::getCustomer('walkin_customer_uuid', $inputs['customer_uuid']);
//            if (empty($walkin_customer)) {
//                return ['success' => false, 'message' => CustomerChecksValidationHelper::checkCustomerAppointmentRules()['message_' . strtolower($inputs['lang'])]['invalid_customer_uuid']];
//            } else {
//                $customer = $walkin_customer;
//                $customer['customer_uuid'] = $walkin_customer['walkin_customer_uuid'];
//            }
//        }

        if (empty($customer)) {
            return ['success' => false, 'message' => CustomerChecksValidationHelper::checkCustomerAppointmentRules()['message_' . strtolower($inputs['lang'])]['invalid_customer_uuid']];
        }

        $customer_name = $customer['first_name'] . ' ' . $customer['last_name'];

        $message_start = $customer_name . ' has ';
        if ((!empty($customer['customer_uuid']) && $customer['customer_uuid'] == $inputs['logged_in_uuid']) /*|| (!empty($customer['walkin_customer_uuid']) && $customer['walkin_customer_uuid'] == $inputs['logged_in_uuid'])*/) {
            $message_start = 'You have ';
        }

        $inputs_array = ['login_user_type' => $inputs['login_user_type'] ?? '','local_timezone' => $inputs['local_timezone'], 'customer_id' => $inputs['customer_id'], 'date' => $inputs['date'], 'from_time' => $inputs['from_time'], 'to_time' => $inputs['to_time']];

        $appointment_check = self::checkCustomerScheduledAppointment($inputs_array);

        if ($appointment_check) {
            return ['success' => false, 'message' => $message_start . 'another appointment scheduled during this time'];
        }

        $class_check = self::checkCustomerClass($inputs_array, $customer);
        if ($class_check) {
            return ['success' => false, 'message' => $message_start . 'another class scheduled during this time'];
        }
        return ['success' => true, 'message' => 'This time slot is free'];
    }

    public static function checkCustomerScheduledAppointment($inputs) {

        $appointments = Appointment::getFreelancerAllAppointments('customer_id', $inputs['customer_id'], ['date' => $inputs['date']]);
        $appointment_response = AppointmentResponseHelper::makeFreelancerAppointmentsResponse($appointments, $inputs['local_timezone'], $inputs['login_user_type']);
        $from_time = CommonHelper::convertTimeToTimezone($inputs['from_time'], 'UTC', $inputs['local_timezone']);

        $to_time = CommonHelper::convertTimeToTimezone($inputs['to_time'], 'UTC', $inputs['local_timezone']);
        $in_schedule = false;
        if (!empty($appointment_response)) {
            foreach ($appointment_response as $single) {
                if (
                        ($single['status'] == 'pending' || $single['status'] == 'confirmed') && (strtotime($single['date']) == strtotime($inputs['date'])) &&
                        ((strtotime($from_time) >= strtotime($single['start_time']) && strtotime($from_time) < strtotime($single['end_time'])) ||
                        (strtotime($to_time) > strtotime($single['start_time']) && strtotime($to_time) < strtotime($single['end_time'])) ||
                        (strtotime($from_time) < strtotime($single['start_time']) && strtotime($to_time) >= strtotime($single['end_time'])) ||
                        (strtotime($from_time) >= strtotime($single['start_time']) && strtotime($to_time) <= strtotime($single['end_time'])) )) {
                    $in_schedule = true;
                    break;
                }
            }
        }
        return $in_schedule;
    }

    public static function checkCustomerClass($inputs, $customer = []) {
        $upcomming_classes = [];
        $pending_class_booking_ids = ClassBooking::pluckClassBookingIds('customer_id', $inputs['customer_id'], 'class_schedule_id', 'pending');
        $confirmed_class_booking_ids = ClassBooking::pluckClassBookingIds('customer_id', $inputs['customer_id'], 'class_schedule_id', 'confirmed');
        $class_booking_ids = array_merge($pending_class_booking_ids, $confirmed_class_booking_ids);

        $get_schedules = ClassSchedule::getMultipleSchedules($class_booking_ids, 'confirmed');

        $upcomming_classes = AppointmentResponseHelper::customerUpcomingClassAppointmentsResponse($get_schedules, $inputs['local_timezone'], $customer, 'confirmed');
        $from_time = CommonHelper::convertTimeToTimezone($inputs['from_time'], 'UTC', $inputs['local_timezone']);
        $to_time = CommonHelper::convertTimeToTimezone($inputs['to_time'], 'UTC', $inputs['local_timezone']);
        $is_class_scheduled = false;
        if (!empty($upcomming_classes)) {
            foreach ($upcomming_classes as $class) {
                if (
                        ($class['status'] == 'pending' || $class['status'] == 'confirmed') && (strtotime($class['date']) == strtotime($inputs['date'])) &&
                        ((strtotime($from_time) >= strtotime($class['start_time']) && strtotime($from_time) < strtotime($class['end_time'])) ||
                        (strtotime($to_time) > strtotime($class['start_time']) && strtotime($to_time) < strtotime($class['end_time'])) ||
                        (strtotime($from_time) < strtotime($class['start_time']) && strtotime($to_time) >= strtotime($class['end_time'])) ||
                        (strtotime($from_time) >= strtotime($class['start_time']) && strtotime($to_time) <= strtotime($class['end_time'])))) {
                    $is_class_scheduled = true;
                    break;
                }
            }
        }
        return $is_class_scheduled;
    }

}
