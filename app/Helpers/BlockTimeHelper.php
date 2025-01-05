<?php

namespace App\Helpers;

use App\Appointment;
use App\Classes;
use App\BlockedTime;

Class BlockTimeHelper {
    /*
      |--------------------------------------------------------------------------
      | BlockTimeHelper that contains time related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use time processes
      |
     */

    /**
     * Description of BlockTimeHelper
     *
     * @author ILSA Interactive
     */
    public static function checkFreelancerScheduledAppointment($inputs) {

        $appointments = Appointment::getFreelancerAllAppointmentWithinDates($inputs['freelancer_id'], $inputs);

        $appointment_response = AppointmentResponseHelper::makeFreelancerAppointmentsResponse($appointments);

        $is_schedule = false;
        if (!empty($appointment_response)) {
            foreach ($appointment_response as $single) {
                if (
                        ($single['date'] >= $inputs['start_date'] && $single['date'] <= $inputs['end_date']) &&
                        (($inputs['from_time'] >= $single['start_time'] && $inputs['from_time'] < $single['end_time']) ||
                        ($inputs['to_time'] > $single['start_time'] && $inputs['to_time'] < $single['end_time']) ||
                        ($inputs['from_time'] < $single['start_time'] && $inputs['to_time'] >= $single['end_time']))) {
                    $is_schedule = true;
                    break;
                }
            }
        }
        return $is_schedule;
    }

    public static function checkFreelancerScheduledClass($inputs) {



        $classes = Classes::getClassesWithinDate('freelancer_id', $inputs['freelancer_id'], $inputs);

        $classes_response = ClassResponseHelper::freelancerClassesResponseAsAppointment($classes);
        $is_class_scheduled = false;
        if (!empty($classes_response)) {
            foreach ($classes_response as $class) {
                if (
                        ($inputs['start_date'] >= $class['date'] && $inputs['end_date'] <= $class['date']) &&
                        (($inputs['from_time'] >= $class['start_time'] && $inputs['from_time'] < $class['end_time']) ||
                        ($inputs['to_time'] > $class['start_time'] && $inputs['to_time'] < $class['end_time']) ||
                        ($inputs['from_time'] < $class['start_time'] && $inputs['to_time'] >= $class['end_time']))) {
                    $is_class_scheduled = true;
                    break;
                }
            }
        }
        return $is_class_scheduled;
    }

    public static function checkFreelancerBlockedTiming($inputs) {



        $blocked_timimgs = BlockedTime::getBlockedTimingsWithinDates('freelancer_id', $inputs['freelancer_id'], $inputs);

        $blocked_response = CalenderResponseHelper::blockedTimingsResponse($blocked_timimgs);
        $is_blocked = false;
        if (!empty($blocked_response)) {
            foreach ($blocked_response as $time) {
                if ($inputs['start_date'] >= $time['start_date'] && $inputs['end_date'] <= $time['end_date']) {
                    if (
                            (($inputs['from_time'] >= $time['start_time'] && $inputs['from_time'] < $time['end_time']) ||
                            ($inputs['to_time'] > $time['start_time'] && $inputs['to_time'] < $time['end_time']) ||
                            ($inputs['from_time'] < $time['start_time'] && $inputs['to_time'] >= $time['end_time']))) {
                        $is_blocked = true;
                        break;
                    }
                }
            }
        }
        return $is_blocked;
    }

}

?>
