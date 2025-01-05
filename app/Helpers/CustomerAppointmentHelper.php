<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;
use App\Appointment;
use App\Notification;
use App\ClassBooking;
use App\Classes;
use App\ClassSchedule;
use App\Customer;

class CustomerAppointmentHelper {

    public static function customerAllAppointments($inputs = []) {

        $validation = Validator::make($inputs, AppointmentValidationHelper::customerAllAppointmentRules()['rules'], AppointmentValidationHelper::customerAllAppointmentRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $customer = Customer::getSingleCustomerDetail('customer_uuid', $inputs['customer_uuid']);
        if (empty($customer)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['invalid_data']);
        }
        $inputs['customer_id'] = $customer['id'];
        $all_appointments = Appointment::getCustomerAllAppointments('customer_id', $inputs['customer_id'], $inputs, (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));

        $response['appointments'] = AppointmentResponseHelper::makeFreelancerAppointmentsResponse($all_appointments, (!empty($inputs['local_timezone']) ? $inputs['local_timezone'] : 'UTC'), $inputs['login_user_type']);

        $response['classes'] = [];
        $class_booking_ids = [];
        $get_schedules = [];
        $upcomming_classes = [];
        if (!empty($inputs['search_params']) && !empty($inputs['search_params']['appoint_type'])) {

            if ($inputs['search_params']['appoint_type'] == 'past' || $inputs['search_params']['appoint_type'] == 'history') {

                $upcomming_classes = self::getCustomerBookingHistory($inputs);

            } elseif ($inputs['search_params']['appoint_type'] == 'upcoming') {

                $upcomming_classes = self::getCustomerUpcomingClassBooking($inputs);
            } else {

                $class_booking_ids = ClassBooking::pluckClassBookingIds('customer_id', $inputs['customer_id'], 'class_schedule_id', $inputs['search_params']['appoint_type']);

                $get_schedules = ClassSchedule::getMultipleSchedules($class_booking_ids);

                $upcomming_classes = AppointmentResponseHelper::customerUpcomingClassAppointmentsResponse($get_schedules, $inputs['local_timezone'], $customer, $inputs['search_params']['appoint_type']);
            }
        }

        $response['classes'] = $upcomming_classes;
        $type = ['notification_type' => 'new_appointment'];

        $update_notification = Notification::updateNotificationCount('receiver_id', $inputs['customer_id'], $type);
        if (empty($response['appointments'])) {
            $response['appointments'] = [];
        }
        if (empty($response['classes'])) {
            $response['classes'] = [];
        }       
        $merger_response = array_merge($response['appointments'], $response['classes']);
//        usort($merger_response, function($a, $b) {
//                $t1 = strtotime($a['datetime']);
//                $t2 = strtotime($b['datetime']);
//                return $t1 - $t2;
//            });
        if ($inputs['search_params']['appoint_type'] == "history") {
            $merger_response = self::sortResponseDescending($merger_response);
        } else {
            $merger_response = self::sortResponseAscending($merger_response);
        }
        $final_response = $merger_response;
        if (isset($inputs['offset']) && isset($inputs['limit'])) {
            $final_response = self::setOffsetLimit($inputs, $merger_response);
        }
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $final_response);
    }

    public static function sortResponseDescending($merger_response) {
        usort($merger_response, function($a, $b) {
            $t1 = strtotime($a['datetime']);
            $t2 = strtotime($b['datetime']);
            return $t2 - $t1;
        });
        return !empty($merger_response) ? $merger_response : [];
    }

    public static function sortResponseAscending($merger_response) {
        usort($merger_response, function($a, $b) {
            $t1 = strtotime($a['datetime']);
            $t2 = strtotime($b['datetime']);
            return $t1 - $t2;
        });
        return !empty($merger_response) ? $merger_response : [];
    }

    public static function setOffsetLimit($inputs, $response_data) {
        $response = [];
        if (!empty($response_data)) {
            $response = array_slice($response_data, $inputs['offset'], $inputs['limit']);
        }
        return $response;
    }

    public static function getCustomerBookingHistory($inputs) {

        $customer = Customer::getSingleCustomerDetail('customer_uuid', $inputs['customer_uuid']);

        $class_booking_ids = ClassBooking::pluckClassBookingIds('customer_id', $inputs['customer_id'], 'class_schedule_id');

        $get_schedules = ClassSchedule::getMultipleSchedules($class_booking_ids, 'history');
        $classes = self::customerHistoryClassAppointmentsResponse($get_schedules, $inputs['local_timezone'], $customer);
        
        return $classes;
    }

    public static function getCustomerUpcomingClassBooking($inputs) {
        $customer = Customer::getSingleCustomerDetail('customer_uuid', $inputs['customer_uuid']);
        $pending_booked_classes = ClassBooking::pluckClassBookingIds('customer_id', $inputs['customer_id'], 'class_schedule_id', 'pending');
        $get_pending_schedules = ClassSchedule::getMultipleSchedules($pending_booked_classes, 'pending');
        $pending_upcomming_classes = AppointmentResponseHelper::customerUpcomingClassAppointmentsResponse($get_pending_schedules, $inputs['local_timezone'], $customer, 'pending');
        $confirmed_booked_classes = ClassBooking::pluckClassBookingIds('customer_id', $inputs['customer_id'], 'class_schedule_id', 'confirmed');
        $get_confirmed_schedules = ClassSchedule::getMultipleSchedules($confirmed_booked_classes, 'confirmed');
        $confirmed_upcomming_classes = AppointmentResponseHelper::customerUpcomingClassAppointmentsResponse($get_confirmed_schedules, $inputs['local_timezone'], $customer, 'confirmed');
        if (empty($pending_upcomming_classes)) {
            $pending_upcomming_classes = [];
        }
        if (empty($confirmed_upcomming_classes)) {
            $confirmed_upcomming_classes = [];
        }
        $upcomming_classes = array_merge($pending_upcomming_classes, $confirmed_upcomming_classes);
        return $upcomming_classes;
    }

    public static function customerHistoryClassAppointmentsResponse($data = [], $local_timezone = 'UTC', $customer = []) {     
        $response = [];
        if (!empty($data)) {
            $key = 0;
            $date = date("Y-m-d");
            foreach ($data as $value) {
                if ($value['class_date'] <= $date) {
                    if ($value['class_date'] == $date && $value['from_time'] >= date('H:i:s')) {
                        continue;
                    }
                    $from_time_saved_conversion = CommonHelper::convertTimeToTimezone($value['from_time'], $value['saved_timezone'], $value['local_timezone']);
                    $from_time_local_conversion = CommonHelper::convertTimeToTimezone($from_time_saved_conversion, $value['local_timezone'], $local_timezone);
                    $to_time_saved_conversion = CommonHelper::convertTimeToTimezone($value['to_time'], $value['saved_timezone'], $value['local_timezone']);
                    $to_time_local_conversion = CommonHelper::convertTimeToTimezone($to_time_saved_conversion, $value['local_timezone'], $local_timezone);

                    $response[$key]['uuid'] = (isset($value['class_uuid']))? $value['class_uuid']:CommonHelper::getClassUuidBYId($value['class_id']);
                    $response[$key]['class_schedule_uuid'] = $value['class_schedule_uuid'];
                    $response[$key]['title'] = $value['classes']['name'];
                    $response[$key]['address'] = $value['classes']['address'];
                    $response[$key]['lat'] = $value['classes']['lat'];
                    $response[$key]['lng'] = $value['classes']['lng'];
                    $response[$key]['package_uuid'] = AppointmentResponseHelper::getCustomerUpcomingClassPackageUuid($value, $customer);
                    $response[$key]['class_image'] = !empty($value['classes']['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_images'] . $value['classes']['image'] : null;
                    $response[$key]['date'] = $value['class_date'];
                    $response[$key]['start_time'] = $from_time_local_conversion;
                    $response[$key]['end_time'] = $to_time_local_conversion;
                    $response[$key]['datetime'] = $value['class_date'] . ' ' . $from_time_local_conversion;
                    $response[$key]['price'] = !empty($value['classes']['price']) ? (double) CommonHelper::getConvertedCurrency($value['classes']['price'], $value['classes']['currency'], $value['classes']['freelancer']['default_currency']) : 0;
                    $response[$key]['type'] = 'class';
                    $response[$key]['status'] = $value['status'];
                    $response[$key]['online_link'] = $value['status'] == 'cancelled' ? 'online video link' : $value['online_link'];
                    $response[$key]['total_session'] = 1;
                    $response[$key]['session_number'] = 1;
                    $response[$key]['package'] = null;
                    $response[$key]['purchase_time'] = null;
                    if (!empty($value['class_bookings'])) {
                        foreach ($value['class_bookings'] as $single_booking) {
                            $classSchedule = CommonHelper::getClassSchedulesUuidBid($value['class_schedule_uuid']);

                            if ($single_booking['class_schedule_id'] == $classSchedule && $single_booking['customer_id'] == $customer['customer_id']) {
                                $response[$key]['purchase_time'] = $single_booking['created_at'];
                                $response[$key]['package'] = PackageResponseHelper::appointmentPackageResponse((!empty($single_booking['package']) ? $single_booking['package'] : []), $single_booking);
                                $response[$key]['total_session'] = !empty($single_booking['total_session']) ? $single_booking['total_session'] : 1;
                                $response[$key]['session_number'] = !empty($single_booking['session_number']) ? $single_booking['session_number'] : 1;
                                $response[$key]['online_link'] = $single_booking['status'] == 'cancelled' ? 'online video link' : $value['online_link'];
                                break;
                            }
                        }
                    }
                    $response[$key]['service'] = AppointmentResponseHelper::appointmentServiceResponse((!empty($value['classes']['freelance_category']) ? $value['classes']['freelance_category'] : []), $value['classes']['currency'], $value['classes']['freelancer']['default_currency']);
                    $response[$key]['freelancer'] = AppointmentResponseHelper::appointmentFreelancerResponse(!empty($value['classes']['freelancer']) ? $value['classes']['freelancer'] : []);
                    $response[$key]['customer'] = AppointmentResponseHelper::appointmentCustomerResponse([]);
                    $response[$key]['customer_name'] = null;
                    $response[$key]['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse();
                    if (!empty($customer)) {
                        $response[$key]['customer'] = AppointmentResponseHelper::appointmentCustomerResponse($customer);
                        $response[$key]['customer_name'] = $customer['first_name'] . ' ' . $customer['last_name'];
                        $response[$key]['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse($customer['profile_image']);
                    }
                    $key++;
                }
            }
            return $response;
        }
    }

    public static function customerClassesResponseAsAppointment($classes = [], $search_params = [], $local_timezone = 'UTC') {
        $response = [];
        if (!empty($classes)) {
            $index = 0;
            foreach ($classes as $value) {
                if (!empty($value['schedule'])) {
                    if (($search_params['status'] == 'confirmed' || $search_params['status'] == 'pending' || $search_params['status'] == 'cancelled') && $value['schedule']['to_time'] > date('H:i:s') || ($search_params['status'] == 'history' && $value['schedule']['to_time'] < date('H:i:s'))) {
                        $from_time_saved_conversion = CommonHelper::convertTimeToTimezone($value['schedule']['from_time'], $value['schedule']['saved_timezone'], $value['schedule']['local_timezone']);
                        $from_time_local_conversion = CommonHelper::convertTimeToTimezone($from_time_saved_conversion, $value['schedule']['local_timezone'], $local_timezone);
                        $to_time_saved_conversion = CommonHelper::convertTimeToTimezone($value['schedule']['to_time'], $value['schedule']['saved_timezone'], $value['schedule']['local_timezone']);
                        $to_time_local_conversion = CommonHelper::convertTimeToTimezone($to_time_saved_conversion, $value['schedule']['local_timezone'], $local_timezone);
                        $response[$index]['uuid'] = $value['class_object']['class_uuid'];
                        $response[$index]['title'] = $value['class_object']['name'];
                        $response[$index]['address'] = $value['class_object']['address'];
                        $response[$index]['date'] = $value['schedule']['class_date'];
                        $response[$index]['start_time'] = $from_time_local_conversion;
                        $response[$index]['end_time'] = $to_time_local_conversion;
                        $response[$index]['datetime'] = $value['schedule']['class_date'] . ' ' . $from_time_local_conversion;
                        $response[$index]['price'] = (double) $value['class_object']['price'];
                        $response[$index]['type'] = 'class';
                        $response[$index]['online_link'] = !empty($value['schedule']['online_link']) ? $value['schedule']['online_link'] : null;
                        $response[$index]['status'] = !empty($value['class_object']['status']) ? $value['class_object']['status'] : 'pending';
                        $response[$index]['freelancer'] = AppointmentResponseHelper::appointmentFreelancerResponse((!empty($value['class_object']) && !empty($value['class_object']['freelancer'])) ? $value['class_object']['freelancer'] : []);
                        $response[$index]['customer'] = AppointmentResponseHelper::appointmentCustomerResponse(!empty($value['customer']) ? $value['customer'] : []);
                        $index++;
                    }
                }
            }
        }
        return $response;
    }

    public static function getAllAppointments($inputs = []) {
        $validation = Validator::make($inputs, AppointmentValidationHelper::getAllAppointmentsRules()['rules'], AppointmentValidationHelper::getAllAppointmentsRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        if ($inputs['login_user_type'] == 'customer') {
            $all_appointments = Appointment::getAllAppointments('customer_uuid', $inputs['logged_in_uuid'], $inputs, (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));
            $response['appointments'] = AppointmentResponseHelper::makeFreelancerAppointmentsResponse($all_appointments, (!empty($inputs['local_timezone']) ? $inputs['local_timezone'] : 'UTC'), $inputs['login_user_type']);
            $booked_classes = ClassBooking::getAllClasses('customer_uuid', $inputs['logged_in_uuid'], $inputs, true, (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));
            $response['classes'] = self::customerClassesResponseAsAppointment($booked_classes, $inputs['local_timezone']);
        }
        if ($inputs['login_user_type'] == 'freelancer') {
            $all_appointments = Appointment::getAllAppointments('freelancer_uuid', $inputs['logged_in_uuid'], $inputs, (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));
            $response['appointments'] = AppointmentResponseHelper::makeFreelancerAppointmentsResponse($all_appointments, (!empty($inputs['local_timezone']) ? $inputs['local_timezone'] : 'UTC'), $inputs['login_user_type']);
            $booked_classes = Classes::getClasses('freelancer_uuid', $inputs['logged_in_uuid'], (!empty($inputs) ? $inputs : []), (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));
            $response['classes'] = ClassResponseHelper::freelancerClassesResponseByDate($booked_classes, $inputs['local_timezone']);
        }
//        $response['appointments'] = AppointmentResponseHelper::makeFreelancerAppointmentsResponse($all_appointments, (!empty($inputs['local_timezone']) ? $inputs['local_timezone'] : 'UTC'));
//        $response['classes'] = [];
//
//        if($inputs['login_user_type']== 'customer') {
//            $booked_classes = ClassBooking::getAllClasses('customer_uuid', $inputs['logged_in_uuid'], $inputs, true, (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));
//            $response['classes'] = self::customerClassesResponseAsAppointment($booked_classes, $inputs['local_timezone']);
//        }
//        if($inputs['login_user_type']== 'freelancer') {
//            $booked_classes = Classes::getClasses('freelancer_uuid', $inputs['logged_in_uuid'], (!empty($inputs) ? $inputs : []), (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));
//            $response['classes'] = ClassResponseHelper::freelancerClassesResponseByDate($booked_classes, $inputs['local_timezone']);
//        }
        // $response['classes'] = self::customerClassesResponseAsAppointment($booked_classes, $inputs['local_timezone']);
        $merger_response = array_merge($response['appointments'], $response['classes']);
        usort($merger_response, function($a, $b) {
            $t1 = strtotime($a['datetime']);
            $t2 = strtotime($b['datetime']);
            return $t1 - $t2;
        });
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $merger_response);
    }

}
