<?php

namespace App\Helpers;

use App\Schedule;
use DB;
use App\Appointment;
use Illuminate\Support\Facades\Validator;
use App\Classes;

Class ScheduleHelper {
    /*
      |--------------------------------------------------------------------------
      | ScheduleHelper that contains all the schedule methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use schedule processes
      |
     */

    public static function updateFreelancerWeeklySchedule($inputs = []) {
        $validation = Validator::make($inputs, ScheduleValidationHelper::freelancerAddScheduleRules()['rules'], ScheduleValidationHelper::freelancerAddScheduleRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $inputs['days'] = isset($inputs['days']) ? $inputs['days'] : [];
        $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);
        // debug from there
        $inputs['delete_day'] = isset($inputs['delete_day']) ? $inputs['delete_day'] : [];
        if (!empty($inputs['days']) || !empty($inputs['delete_day'])) {
            return self::updateWeeklyScheduleInputsProcess($inputs);
        }
        $schedule = Schedule::getFreelancerSchedule('freelancer_id', $inputs['freelancer_id']);
        $response = FreelancerScheduleResponseHelper::freelancerScheduleResponse($schedule, $inputs['local_timezone']);
        DB::commit();
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function updateWeeklyScheduleInputsProcess($inputs) {
        if (!empty($inputs['days'])) {
            foreach ($inputs['days'] as $day) {
                if (empty($day['time_slots'])) {
                    return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['invalid_data_error']);
                }
            }
            $check_existing = self::processUpdateScheduleCheck($inputs);
            if (!$check_existing['success']) {
                return CommonHelper::jsonErrorResponse($check_existing['message']);
            }
        }
        if (!empty($inputs['delete_day'])) {
            $delete_inputs = $inputs;
            foreach ($inputs['delete_day'] as $dlt_day){
                $delete_inputs['days'][]['day'] = $dlt_day;
            }

            $check = self::processUpdateScheduleCheck($delete_inputs);
            if (!$check['success']) {
                return CommonHelper::jsonErrorResponse($check['message']);
            }
            Schedule::deleteFreelancerSchedule($inputs['freelancer_id'], $inputs['delete_day']);
            /*if (!$delete) {
                return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['save_schedule_error']);
            }*/
        }
        
        return self::processUpdateSchedule($inputs);
    }

    public static function processUpdateScheduleCheck($inputs) {
        $response = ['success' => true, 'message' => 'successful request'];
        //debug here 
        $check_appointment_exists = self::checkScheduledAppointments($inputs);
        if ($check_appointment_exists['success']) {
            $error_message = FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['update_schedule_appointment_error'] . $check_appointment_exists['data']['appointment_date'];
            return ['success' => false, 'message' => $error_message];
        }
        $check_class_exists = self::checkScheduledClasses($inputs);
        if ($check_class_exists['success']) {
            $error_message = FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['update_schedule_class_error'] . $check_class_exists['data']['class_date'];
            return ['success' => false, 'message' => $error_message];
        }
        return $response;
    }

    public static function processUpdateSchedule($inputs) {

        $schedule_inputs = [];
        $days_array = [];
        $index = 0;
        foreach ($inputs['days'] as $day) {
            if (!in_array($day['day'], $days_array)) {
                array_push($days_array, $day['day']);
            }
            foreach ($day['time_slots'] as $slot) {
                $slot['day'] = $day['day'];
                $slot['freelancer_id'] = $inputs['freelancer_id'];
                $validation = Validator::make($slot, FreelancerValidationHelper::freelancerDailyScheduleRules()['rules'], FreelancerValidationHelper::freelancerDailyScheduleRules()['message_' . strtolower($inputs['lang'])]);
                if ($validation->fails()) {
                    return CommonHelper::jsonErrorResponse($validation->errors()->first());
                }
                $schedule_inputs[$index]['schedule_uuid'] = UuidHelper::generateUniqueUUID('schedules', 'schedule_uuid');
                $schedule_inputs[$index]['freelancer_id'] = $slot['freelancer_id'];
                $schedule_inputs[$index]['day'] = $slot['day'];
                $schedule_inputs[$index]['from_time'] = $slot['from_time'];
                $schedule_inputs[$index]['to_time'] = $slot['to_time'];
                $schedule_inputs[$index]['saved_timezone'] = "UTC";
                $schedule_inputs[$index]['local_timezone'] = $inputs['local_timezone'];
                $index++;
            }
        }

        return self::updateSchedule($inputs, $schedule_inputs, $days_array);
    }

    public static function updateSchedule($inputs, $schedule_inputs, $days_array) {
        Schedule::deleteFreelancerSchedule($inputs['freelancer_id'], $days_array);
        $freelance_schedule_resp = Schedule::saveSchedule($schedule_inputs);
        if (!$freelance_schedule_resp) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['save_schedule_error']);
        }
        $schedule = Schedule::getFreelancerSchedule('freelancer_id', $inputs['freelancer_id']);
        $response = FreelancerScheduleResponseHelper::freelancerScheduleResponse($schedule, $inputs['local_timezone']);
        DB::commit();
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function checkScheduledAppointments($inputs) {
        $response = ['success' => false, 'data' => []];
        $upcomming_appointments = Appointment::getUpcomingAppointments('freelancer_id', $inputs['freelancer_id']);
        if (!empty($upcomming_appointments)) {
            foreach ($upcomming_appointments as $appointment) {
                if ($appointment['status'] == 'pending' || $appointment['status'] == 'confirmed') {
                    $appointment_day = date("l", strtotime($appointment['appointment_date']));
                    if (isset($inputs['days']) && !empty($inputs['days'])) {
                        foreach ($inputs['days'] as $day) {
                            if (!empty($day['day'])) {
                                if (strtolower($appointment_day) == strtolower($day['day'])) {
                                    $response = ['success' => true, 'data' => $appointment];
                                    return $response;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $response;
    }

    public static function checkScheduledClasses($inputs) {
        $response = ['success' => false, 'data' => []];
        $upcomming_classes = Classes::getFreelancerUpcomingClasses('freelancer_id', $inputs['freelancer_id'], date("Y-m-d"));

        if (!empty($upcomming_classes)) {
            foreach ($inputs['days'] as $day) {
                foreach ($upcomming_classes as $class) {
                    foreach ($class['schedule'] as $schedule) {
                        if (($class['status'] == 'pending' || $class['status'] == 'confirmed') && ($schedule['status'] == 'pending' || $schedule['status'] == 'confirmed')) {
                            $class_day = date("l", strtotime($schedule['class_date']));
                            if (isset($day['day']) && strtolower($class_day) == strtolower($day['day'])) {
                                $response = ['success' => true, 'data' => $schedule];
                                return $response;
                            }
                        }
                    }
                }
            }
        }
        return $response;
    }


    public static function saveWeeklyScheduleData($inputs = []) {
        DB::beginTransaction(); // Transaction shuru karo
    
        $validation = Validator::make($inputs, ScheduleValidationHelper::freelancerAddScheduleRules()['rules'], ScheduleValidationHelper::freelancerAddScheduleRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
    
        if (!empty($inputs['days'])) {
            $schedule_inputs = self::saveWeeklyScheduleInputsProcess($inputs);
    
            return self::saveWeeklyScheduleProcess($inputs, $schedule_inputs);
        }
    
        DB::rollBack();
        return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['invalid_data_error']);
    }
    

    public static function saveWeeklyScheduleProcess($inputs = [], $day_inputs = []) {
        $freelance_schedule_resp = Schedule::saveSchedule($day_inputs);
        if (!$freelance_schedule_resp) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['save_schedule_error']);
        }
        $freelanceId =CommonHelper::getRecordByUuid('freelancers','freelancer_uuid', $inputs['freelancer_uuid']);
        $profile_update['freelancer_uuid'] = $inputs['freelancer_uuid'];
        if (!empty($inputs['onboard_count'])) {
            $profile_update['onboard_count'] = $inputs['onboard_count'];
        }
        $profile_update['lang'] = $inputs['lang'];
        $save_profile = FreelancerHelper::updateFreelancer($profile_update);

        $result = json_decode(json_encode($save_profile));
        if ($result->original->success) {
            $schedule = Schedule::getFreelancerSchedule('freelancer_id', $freelanceId);

            $response = FreelancerScheduleResponseHelper::freelancerScheduleResponse($schedule, $inputs['local_timezone']);
            DB::commit();
            return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
        }
        DB::rollBack();
        return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['save_schedule_error']);
    }

    public static function saveWeeklyScheduleInputsProcess($inputs) {
        $index = 0;
        $schedule_inputs = [];
        foreach ($inputs['days'] as $day) {
            if (empty($day['time_slots'])) {
                return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['invalid_data_error']);
            }

            foreach ($day['time_slots'] as $slot) {

                $slot['day'] = $day['day'];

                $slot['freelancer_id'] = CommonHelper::getRecordByUuid('freelancers','freelancer_uuid', $inputs['freelancer_uuid']);
                $validation = Validator::make($slot, FreelancerValidationHelper::freelancerDailyScheduleRules()['rules'], FreelancerValidationHelper::freelancerDailyScheduleRules()['message_' . strtolower($inputs['lang'])]);
                if ($validation->fails()) {
                    return CommonHelper::jsonErrorResponse($validation->errors()->first());
                }
                $schedule_inputs[$index]['schedule_uuid'] = UuidHelper::generateUniqueUUID('schedules', 'schedule_uuid');
                $schedule_inputs[$index]['freelancer_id'] = $slot['freelancer_id'];
                $schedule_inputs[$index]['day'] = $slot['day'];
                $schedule_inputs[$index]['from_time'] = $slot['from_time'];
                $schedule_inputs[$index]['to_time'] = $slot['to_time'];
                $schedule_inputs[$index]['saved_timezone'] = "UTC";
                $schedule_inputs[$index]['local_timezone'] = $inputs['local_timezone'];
                $index++;
            }
        }

        return $schedule_inputs;
    }

}

?>
