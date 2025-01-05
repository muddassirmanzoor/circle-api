<?php

namespace App\Helpers;

use App\Schedule;
use App\Appointment;
use App\Classes;
use App\BlockedTime;
use App\FreelanceCategory;
use Illuminate\Support\Facades\Validator;
use App\Helpers\FreelancerScheduleValidationHelper;

Class FreelancerschedulerHelper {

    public static function getFreelancerAvailableSlots($inputs) {
        $validation = Validator::make($inputs, FreelancerScheduleValidationHelper::schedulerRules()['rules'], FreelancerScheduleValidationHelper::schedulerRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        // $freelancer_sub_category = FreelanceCategory::getFreelancerCategory('freelancer_category_uuid', $inputs['freelancer_category_uuid']);

        // unset($inputs['freelancer_category_uuid']);
        // if (empty($freelancer_sub_category)) {
        //     return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['freelancer_category_error']);
        // }

//        $inputs['time_duration'] = $freelancer_sub_category['duration'] ?? 30;

        $inputs['time_duration'] = 15;

        $schedule = Schedule::getFreelancerScheduleByDay($inputs);

        $freelancer_schedule = FreelancerScheduleResponseHelper::freelancerScheduleResponse($schedule, $inputs['local_timezone']);

        if (empty($freelancer_schedule)) {
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['schedule_error']);
        }

        $freelanceId = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);


        $slots = self::setFreelancerSlots($inputs, $freelancer_schedule);

        $inputs['from_slots'] = true;
        $blocked_time = BlockedTime::getBlockTimeForSlots('freelancer_id', $inputs['freelancer_uuid'], $inputs['date']);

        $block_time_slots = self::getBlockTimeSlots($inputs, $slots, $blocked_time);

        $classes_time = Classes::getNotCancelClasses('freelancer_id', $freelanceId, $inputs);

        $classes_time_slots = self::getClassesSlots($inputs, $slots, $classes_time);

        $appointment_time = Appointment::getFreelancerAllAppointments('freelancer_id', $freelanceId, $inputs);

        $appontment_slots_status = self::getSlotsAccordingStatus($appointment_time);

        $appontment_slots = self::getBlockTimeSlots($inputs, $slots, $appontment_slots_status);

        $pass_time_slots = self::getPassedTimeSlots($inputs, $slots);

//         current date pass time disable slots get
        $all_reserve_slots = array_merge($appontment_slots, $block_time_slots, $classes_time_slots, $pass_time_slots);
        $all_reserve_slots = array_values(array_unique($all_reserve_slots));
        $final_slots = self::getFinalSlots($inputs, $slots, $all_reserve_slots);

        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $final_slots);
    }

    public static function getPassedTimeSlots($inputs, $slots) {

        $reserve_slots = [];
        if ($inputs['date'] == date('Y-m-d')) {

            date_default_timezone_set($inputs['local_timezone']);
            $local_time = date('H:i');

//            $set_time = self::setAmPmInTime($local_time);
            foreach ($slots['slots'] as $key => $slot) {
                $from_time_saved_conversion = $slot;
                if (strtotime($local_time) >= strtotime($from_time_saved_conversion)) {
                    $reserve_slots[] = $key;
                }
            }
        }
        return $reserve_slots;
    }

    public static function getSlotsAccordingStatus($available_slots) {
        $status = ['confirmed', 'pending'];
        $response = [];
        if (!empty($available_slots)) {
            foreach ($available_slots as $key => $slot) {

                if (in_array($slot['status'], $status)) {
                    $response[$key] = $slot;
                }
            }
        }
        return !empty($response) ? array_values($response) : $response;
    }

    public static function getClassesSlots($inputs, $slots, $classes_time) {
        $reserve_slots = [];
        if (!empty($classes_time)) {
            foreach ($classes_time as $class) {
                if (!empty($class['schedule'])) {
                    $reserve = self::getBlockTimeSlots($inputs, $slots, $class['schedule']);
                    $reserve_slots = array_merge($reserve_slots, $reserve);
                }
            }
        }
        return $reserve_slots;
    }

    public static function setFreelancerSlots($inputs, $freelancer_schedule) {
        $result = [];
        $slots = [];
        $timezone = '';

        if (!empty($freelancer_schedule)) {
            foreach ($freelancer_schedule as $schedule) {
                foreach ($schedule['timings'] as $key => $timing) {
//                    $from_time = CommonHelper::convertTimeToTimezone($timing['from_time'], 'UTC', $inputs['local_timezone']);
//                    $to_time = CommonHelper::convertTimeToTimezone($timing['to_time'], 'UTC', $inputs['local_timezone']);
//                    $slot = self::createTimeRange($from_time, $to_time, $inputs['time_duration'], 24);
                    $slot = self::createTimeRange($timing['from_time'], $timing['to_time'], $inputs['time_duration'], 24);
                    unset($slot[count($slot) - 1]);
                    $slots = array_merge($slots, $slot);
                    $timezone = $schedule['saved_timezone'];
                }
            }
        }
        $result['slots'] = $slots;
        $result['saved_timezone'] = $inputs['local_timezone'];

        return $result;
    }

    public static function createTimeRange($start, $end, $interval = '30', $format = '24') {
        $startTime = strtotime($start);
        $endTime = strtotime($end);
        $returnTimeFormat = ($format == '12') ? 'g:i A' : 'G:i';
        $returnTimeFormat24 = ($format == '24') ? 'g:i A' : 'G:i';

        $current = time();

        $addTime = strtotime('+' . $interval . ' mins', $current);

        $diff = $addTime - $current;
        $times = array();
        while ($startTime < $endTime) {
//            $times['times'][] = date($returnTimeFormat, $startTime);
//            $times['times24'][] = date($returnTimeFormat24, $startTime);
            $times[] = date($returnTimeFormat24, $startTime);
            $startTime += $diff;
        }
//        $times['times'][] = date($returnTimeFormat, $startTime);
//        $times['times24'][] = date($returnTimeFormat24, $startTime);
        $times[] = date($returnTimeFormat24, $startTime);

        return $times;
    }

    public static function getReserveSlots($slots, $available_slots) {
        $reserve_slots = [];
        if (!empty($available_slots)) {
            foreach ($available_slots as $avail_slt) {
                $set_time = self::setAmPmInTime($avail_slt['from_time']);
                if (in_array($set_time, $slots)) {
                    $reserve_slots[] = array_search($set_time, $slots);
                }
            }
        }
        return $reserve_slots;
    }

//    public static function getBlockTimeSlots($inputs, $slots, $blocked_time) {
//        $reserve_slots = [];
//        if (!empty($blocked_time)) {
//            foreach ($blocked_time as $key => $value) {
//                $from_time = CommonHelper::convertTimeToTimezone($value['from_time'], 'UTC', $inputs['local_timezone']);
//                $to_time = CommonHelper::convertTimeToTimezone($value['to_time'], 'UTC', $inputs['local_timezone']);
//                $set_slots = self::createTimeRange($from_time, $to_time, 15);
//                foreach ($slots['slots'] as $key => $slot) {
//                    for ($i = 0; $i < count($set_slots); $i++) {
//                        if ($key < count($slots['slots']) - 1) {
//                            $last_index = $slots['slots'][$key + 1];
//                        }
//                        if ($key === count($slots['slots'])) {
//                            $last_index = $slot;
//                        }
//                        if (((strtotime($slot) == strtotime($set_slots[$i])) && (strtotime($set_slots[$i]) == strtotime($last_index))) ||
//                                ((strtotime($set_slots[$i]) < strtotime($last_index)) && (strtotime($set_slots[$i]) > strtotime($slot)))) {
//                            $reserve[] = $slot;
//                            $reserve_slots[] = $key;
//                        }
//                    }
//                }
//            }
//        }
//        $array = array_values(array_unique($reserve_slots));
////        unset($array[count($array) - 1]);
//        return $array;
//    }

    public static function getBlockTimeSlots($inputs, $slots, $blocked_time) {

        $reserve_slots = [];
        if (!empty($blocked_time)) {
            $index = 0;
            foreach ($blocked_time as $key => $value) {

                    $from_time = CommonHelper::convertTimeToTimezone($value['from_time'], 'UTC', $inputs['local_timezone']);
                    $to_time = CommonHelper::convertTimeToTimezone($value['to_time'], 'UTC', $inputs['local_timezone']);
                    $set_slots = self::createTimeRange($from_time, $to_time, 15);
                    foreach ($slots['slots'] as $key => $slot) {
                        foreach ($set_slots as $blocked_slot) {
                            if ((strtotime($slot) == strtotime($blocked_slot))) {
                                $reserve[$index] = $slot;
                                $reserve_slots[$index] = $key;
                                $index++;
                            }
                        }
                    }
                    if (!empty($reserve_slots) && ($reserve_slots[array_key_last($reserve_slots)] < (count($slots['slots']) - 1))) {
                        $index = (count($reserve_slots) - 1);
                        unset($reserve_slots[$index]);
                    }


            }
        }
        $array = array_values(array_unique($reserve_slots));
        return $array;
    }


    public static function setAmPmInTime($time) {
        if (!empty($time)) {
            $type = '';
            $exp = explode(':', $time);
            if ($exp[0] >= 12) {
                $time = ($exp[0] > 12) ? $exp[0] - 12 : 12;
                $type = 'PM';
            } else {
                if (substr($exp[0], 0, 1) == 0) {
                    $time = substr($exp[0], 1);
                } else {
                    $time = $exp[0];
                }
                $type = 'AM';
            }
            return $time . ":" . $exp[1] . " " . $type;
        }
        return '00:00:00';
    }

    public static function getFinalSlots($inputs, $slots, $reserve_slots) {

        $day = \Carbon\Carbon::parse($inputs['date'])->format('D');
        $final_array = [];

        if (!empty($slots)) {
            foreach ($slots['slots'] as $key => $slot) {
//                $time_slots = CommonHelper::convertTimeToTimezone($slot, 'UTC', $inputs['local_timezone']);
                $final_array[$key]['slot_uuid'] = $key . CommonHelper::setDbDateFormat($inputs['date'], 'ymdh') . $key;
//                $final_array[$key]['time'] = self::setAmPmInTime($slot);
                $final_array[$key]['time'] = $slot;
                $final_array[$key]['day'] = $day;
                $final_array[$key]['slot_duration'] = $inputs['time_duration'];
                $final_array[$key]['held_date'] = CommonHelper::setDbDateFormat($inputs['date'], 'd M, Y');
                if (in_array($key, $reserve_slots)) {
                    $final_array[$key]['available'] = false;
                } else {
                    $final_array[$key]['available'] = true;
                }
            }
//            unset($final_array[count($final_array) - 1]);
        }
        return $final_array;
    }

}

?>
