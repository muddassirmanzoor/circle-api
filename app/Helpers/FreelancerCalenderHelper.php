<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;
use App\Freelancer;
use App\Schedule;
use App\Classes;
use App\Appointment;

Class FreelancerCalenderHelper {
    /*
      |--------------------------------------------------------------------------
      | FreelancerCalenderHelper that contains calender related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use calender processes
      |
     */

    /**
     * Description of FreelancerCalenderHelper
     *
     * @author ILSA Interactive
     */
    public static function getFreelancerCalender($inputs = []) {
        $validation = Validator::make($inputs, CalenderValidationHelper::getFreelancerCalenderRules()['rules'], CalenderValidationHelper::getFreelancerCalenderRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        
        $freelancer = Freelancer::getFreelancerDetail('freelancer_uuid', $inputs['freelancer_uuid']);
        $freelancerId = $freelancer['id'];
        if (empty($freelancer)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['invalid_data']);
        }
        $schedule = Schedule::getFreelancerSchedule('freelancer_id', $freelancerId);

        $response['schedule'] = FreelancerScheduleResponseHelper::freelancerScheduleResponse($schedule, $inputs['local_timezone']);
        $appointments = Appointment::getUpcomingAppointments('freelancer_id', $freelancerId, 20, 0);

        $appointments_response = AppointmentResponseHelper::upcomingAppointmentsResponse($appointments, $inputs['local_timezone']);

        if (empty($appointments_response)) {
            $appointments_response = [];
        }

        $classes = Classes::getFreelancerUpcomingClasses('freelancer_id', $freelancerId, date("Y-m-d"), 20, 0);

        $classes_response = ClassResponseHelper::upcomingClassesResponseAsAppointment($classes, $inputs['local_timezone']);

        $response['appointments'] = array_merge($appointments_response, $classes_response);

        usort($response['appointments'], function($a, $b) {
            $t1 = strtotime($a['datetime']);
            $t2 = strtotime($b['datetime']);
            return $t1 - $t2;
        });

        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

}

?>
