<?php

namespace App\Helpers;

use App\Client;
use Illuminate\Support\Facades\Validator;
use App\Appointment;
use App\Subscription;
use App\ClassBooking;
use App\Classes;
use App\Customer;
use App\ClassSchedule;
use App\Follower;

Class ClientHelper {
    /*
      |--------------------------------------------------------------------------
      | ClientHelper that contains client related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use client processes
      |
     */

    /**
     * Description of ClientHelper
     *
     * @author ILSA Interactive
     */
    public static function addClient($inputs = []) {

        $inputs['client_uuid'] = UuidHelper::generateUniqueUUID("clients", "client_uuid");
        $validation = Validator::make($inputs, ClientValidationHelper::addClientRules()['rules'], ClientValidationHelper::addClientRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return ['success' => false, 'messages' => $validation->errors()->first()];
        }

        $existing_client = Client::getClient($inputs['freelancer_id'], $inputs['customer_id']);
        if (!empty($existing_client)) {
            $response = ClientResponseHelper::prepareClientResponse($existing_client);
            return ['success' => true, 'data' => $response, 'messages' => ClientValidationHelper::addClientRules()['message_' . strtolower($inputs['lang'])]['save_client_success']];
        }
        $save_client = Client::saveClient($inputs);
        if (!$save_client) {
            return ['success' => false, 'messages' => ClientValidationHelper::addClientRules()['message_' . strtolower($inputs['lang'])]['save_client_error']];
        }

        $response = ClientResponseHelper::prepareClientResponse($save_client);
        return ['success' => true, 'data' => $response, 'messages' => ClientValidationHelper::addClientRules()['message_' . strtolower($inputs['lang'])]['save_client_success']];
    }

    public static function getClientDetails($inputs = []) {
        $validation = Validator::make($inputs, ClientValidationHelper::getClientDetails()['rules'], ClientValidationHelper::getClientDetails()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return ['success' => false, 'messages' => $validation->errors()->first()];
        }

        $client['walkin_customer'] = [];
        if (!empty($inputs['client_uuid'])) {
            $client = Client::getClientDetails('client_uuid', $inputs['client_uuid']);
        }elseif (empty($inputs['client_uuid']) && $inputs['type'] == "customer") {
            $client['customer'] = Customer::getSingleCustomerDetail('customer_uuid', $inputs['customer_uuid']);
            $client['customer_id'] = CommonHelper::getCutomerIdByUuid( $inputs['customer_uuid']);
            $client['freelancer_id'] = CommonHelper::getRecordByUserType($inputs['login_user_type'], $inputs['logged_in_uuid'],'id');

        }

        elseif (empty($inputs['client_uuid']) && $inputs['type'] == "walkin_customer") {
            $client['walkin_customer'] = \App\WalkinCustomer::getCustomer('walkin_customer_uuid', $inputs['customer_uuid']);
            $client['customer_uuid'] = $inputs['customer_uuid'];
            $client['freelancer_uuid'] = $inputs['logged_in_uuid'];
        }
        if (empty($client)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['invalid_data']);
        }

        $client_data = !empty($client['customer']) ? $client['customer'] : [];
        if (empty($client_data)) {
            $client_data = !empty($client['walkin_customer']) ? $client['walkin_customer'] : [];
        }

        $client_data['appointments_count'] = Appointment::getClientAppointmentsCount($client['freelancer_id'], $client['customer_id'],'>');
        $client_data['appointments_revenue'] = Appointment::getClientAppointmentsRevenue($client['freelancer_id'], $client['customer_id']);
        $client_data['history_count'] = Appointment::getClientAppointmentsCount($client['freelancer_id'], $client['customer_id'],'<');

        $limit = !empty($inputs['limit']) ? $inputs['limit'] : null;
        $offset = !empty($inputs['offset']) ? $inputs['offset'] : null;
        $appointments = Appointment::getClientAppointments($client['freelancer_id'], $client['customer_id'], $limit, $offset);
        $chat_data = self::getClientChatData($client);
        $response['client'] = ClientResponseHelper::prepareClientDetailsResponse($client_data, $chat_data);
        $response['appointments'] = AppointmentResponseHelper::makeFreelancerAppointmentsResponse($appointments, $inputs['local_timezone'], $inputs['login_user_type']);
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getClientChatData($inputs) {
        $data = [];

        if (!empty($inputs)) {
            $data['has_appointment'] = false;
            $has_booking = false;

            $check_appointment = Appointment::oneDayAppointmentCheck($inputs['customer_id'], $inputs['freelancer_id']);

            if (!empty($check_appointment) && $check_appointment[0]['status'] == "pending") {
                $has_booking = self::confirmedAppointmentCheck($check_appointment);
//                $has_booking = true;
            }

            if (!empty($check_appointment) && $check_appointment[0]['status'] == "confirmed" && $has_booking == false) {
                $has_booking = self::confirmedAppointmentCheck($check_appointment);
            }

            if ($has_booking == false) {

                $get_completed_apointment = Appointment::completedAppointmentCheck($inputs['customer_id'], $inputs['freelancer_id']);

                $has_booking = self::completedAppointmentCheck($get_completed_apointment);

            }

            if ($has_booking == false) {
                $has_booking = self::checkClasses($inputs);
            }

            $check_subscription = Subscription::checkSubscriber($inputs['customer_id'], $inputs['freelancer_id']);

            $check_follower = Follower::checkFollowing($inputs['customer_id'], $inputs['freelancer_id']);
            $data['has_subscription'] = !empty($check_subscription) ? true : false;
            $data['has_appointment'] = ($has_booking) ? true : false;
            $data['has_followed'] = ($check_follower) ? true : false;
        }
        return !empty($data) ? $data : [];
    }

    public static function completedAppointmentCheck($appointment_time) {
        if (!empty($appointment_time)) {
            $to_time = now();
            foreach ($appointment_time as $key => $time) {
                $from_time = $time['appointment_date'] . ' ' . $time['from_time'];
                $calculate_difference = CommonHelper::getTimeDifferenceInHours($from_time, $to_time);
                if ($calculate_difference->d < 1 && $calculate_difference->h <= 24) {
//                if ($calculate_difference->d < 1 && $calculate_difference->h <= 24 || $calculate_difference->h > 0) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function confirmedAppointmentCheck($appointment_time) {
        if (!empty($appointment_time)) {
            $to_time = now();
            foreach ($appointment_time as $key => $time) {
                $from_time = $time['appointment_date'] . ' ' . $time['from_time'];
                if ((strtotime($time['appointment_date']) <= strtotime(date('Y-m-d')))) {
                    $calculate_difference = CommonHelper::getTimeDifferenceInHours($from_time, $to_time);
                    if ($calculate_difference->d < 1 && $calculate_difference->h < 24) {
                        return true;
                    }
                }
                if ((strtotime($time['appointment_date']) >= strtotime(date('Y-m-d')))) {
                    $calculate_difference = CommonHelper::getTimeDifferenceInHours($from_time, $to_time);
                    if ($calculate_difference->d >= 1 || $calculate_difference->h >= 1) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public static function checkClasses($inputs) {
        if (!empty($inputs)) {
            $has_booking = false;

            $data = ClassBooking::getCustomerClassesWRTFreelancer($inputs['customer_id'], $inputs['freelancer_id']);

            if (!empty($data) && (isset($data[0]['status'])) && $data[0]['status'] == "pending") {
                $schedule_ids = self::prepareScheduleIds($data);
                $get_schedules = ClassSchedule::getClassSchedules('class_schedule_id', $schedule_ids);
                $has_booking = self::confirmedClassCheck($get_schedules);
//                $has_booking = true;
            }

            if (!empty($data) && isset($data[0]['status']) && $data[0]['status'] == "confirmed" && $has_booking == false) {
                $schedule_ids = self::prepareScheduleIds($data);

                $get_schedules = ClassSchedule::getClassSchedules('id', $schedule_ids);

                $has_booking = self::confirmedClassCheck($get_schedules);
            }

            if ($has_booking == false) {
                $completed_classes = ClassBooking::getCompletedClassesWRTFreelancer($inputs['customer_id'], $inputs['freelancer_id']);
                if (!empty($completed_classes)) {
                    $schedule_ids = self::prepareScheduleIds($completed_classes);
                    $get_schedules = ClassSchedule::getClassSchedules('id', $schedule_ids);
                    $has_booking = self::completedClassesCheck($get_schedules);
                }
            }
        }

        return $has_booking ? true : false;
    }

    public static function prepareScheduleIds($data) {
        if (!empty($data)) {
            $schedule_ids = [];
            foreach ($data as $key => $data_ids) {
                if (!in_array($data_ids['class_schedule_id'], $schedule_ids)) {
                    array_push($schedule_ids, $data_ids['class_schedule_id']);
                }
            }
        }
        return $schedule_ids;
    }

    public static function classesCheckavailabitlity($data) {
        if (!empty($data)) {
            $to_time = now();
            foreach ($data as $key => $time) {
                $from_time = $time['class_date'] . ' ' . $time['from_time'];
                $calculate_difference = CommonHelper::getTimeDifferenceInHours($from_time, $to_time);
                if ($calculate_difference->d < 1 && $calculate_difference->h < 24) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function confirmedClassCheck($data) {
        if (!empty($data)) {
            $to_time = now();
            foreach ($data as $key => $time) {
                $from_time = $time['class_date'] . ' ' . $time['from_time'];
                if ((strtotime($time['class_date']) <= strtotime(date('Y-m-d')))) {
                    $calculate_difference = CommonHelper::getTimeDifferenceInHours($from_time, $to_time);
                    if ($calculate_difference->d < 1 && $calculate_difference->h < 24) {
                        return true;
                    }
                }
                if ((strtotime($time['class_date']) >= strtotime(date('Y-m-d')))) {
                    $calculate_difference = CommonHelper::getTimeDifferenceInHours($from_time, $to_time);
                    if ($calculate_difference->d >= 1 || $calculate_difference->h >= 1) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public static function completedClassesCheck($data) {
        if (!empty($data)) {
            $to_time = now();
            foreach ($data as $key => $time) {
                $from_time = $time['class_date'] . ' ' . $time['from_time'];
                $calculate_difference = CommonHelper::getTimeDifferenceInHours($from_time, $to_time);
                if ($calculate_difference->d < 1 && $calculate_difference->h <= 24) {
//                if ($calculate_difference->d < 1 && $calculate_difference->h <= 24 || $calculate_difference->h > 0) {
                    return true;
                }
            }
        }
        return false;
    }

}

?>
