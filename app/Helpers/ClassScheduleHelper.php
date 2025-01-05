<?php

namespace App\Helpers;

use App\Package;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Classes;
use App\ClassSchedule;
use App\ClassBooking;

class ClassScheduleHelper
{
    /*
      |--------------------------------------------------------------------------
      | ClassScheduleHelper that contains all the class related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use Class processes
      |
     */

    public static function searchClassSchedule($inputs)
    {
        $validation = Validator::make($inputs, ClassValidationHelper::searchClassScheduleRules()['rules'], ClassValidationHelper::searchClassScheduleRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);
        $classes = Classes::getAvailableClasses('freelancer_id', $inputs['freelancer_id'], $inputs, (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));

        if (empty($classes)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['no_class_schedule_available']);
        }
        $response = self::setSearchClassScheduleResponse($classes, $inputs['currency'], $inputs['local_timezone']);
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function setSearchClassScheduleResponse($classes, $to_currency, $local_timezone = 'UTC')
    {
        $response = [];
        foreach ($classes as $key => $class) {

            if (!empty($class['single_schedule'])) {

                $from_time_saved_conversion = CommonHelper::convertTimeToTimezone($class['single_schedule']['from_time'], $class['single_schedule']['saved_timezone'], $class['single_schedule']['local_timezone']);
                $from_time_local_conversion = CommonHelper::convertTimeToTimezone($from_time_saved_conversion, $class['single_schedule']['local_timezone'], $local_timezone);
                $to_time_saved_conversion = CommonHelper::convertTimeToTimezone($class['single_schedule']['to_time'], $class['single_schedule']['saved_timezone'], $class['single_schedule']['local_timezone']);
                $to_time_local_conversion = CommonHelper::convertTimeToTimezone($to_time_saved_conversion, $class['single_schedule']['local_timezone'], $local_timezone);

                $response[$key]['class_uuid'] = $class['class_uuid'];
                $response[$key]['class_schedule_uuid'] = $class['single_schedule']['class_schedule_uuid'];
                $response[$key]['freelancer_uuid'] = CommonHelper::getRecordByUuid('freelancers', 'id', $class['freelancer_id'], 'freelancer_uuid');
                $response[$key]['name'] = $class['name'];
                $response[$key]['description'] = $class['notes'];
                $response[$key]['total_students'] = $class['no_of_students'];
                $enrolled_profiles = ClassResponseHelper::enrolledStudents((!empty($class['single_schedule']['class_bookings']) ? $class['single_schedule']['class_bookings'] : []), $class['single_schedule']['class_schedule_uuid']);
                $response[$key]['confirm_students'] = count($enrolled_profiles);
                $response[$key]['members'] = $enrolled_profiles;
                $response[$key]['price'] = !empty($class['price']) ? (float) CommonHelper::getConvertedCurrencyExtended($class['price'], $class['currency'], $to_currency) : 0;
                $response[$key]['duration'] = (int) CommonHelper::getTimeDifferenceInMinutes($from_time_local_conversion, $to_time_local_conversion);


                $response[$key]['start_class_date'] = CommonHelper::datePartial(CommonHelper::convertMyDBDateIntoLocalDate(strtotime($class['start_date']), 'UTC', $class['single_schedule']['local_timezone']));
                $response[$key]['end_class_date'] = CommonHelper::datePartial(CommonHelper::convertMyDBDateIntoLocalDate(strtotime($class['end_date']), 'UTC', $class['single_schedule']['local_timezone']));
                //$response[$key]['end_class_date'] = CommonHelper::datePartial($class['end_date']);


                $response[$key]['address'] = $class['address'];
                $response[$key]['lat'] = $class['lat'];
                $response[$key]['lng'] = $class['lng'];
                $response[$key]['start_time'] = $from_time_local_conversion;
                $response[$key]['end_time'] = $to_time_local_conversion;
                $response[$key]['on_held'] = $class['single_schedule']['schedule_type'];
                $response[$key]['held_date'] = CommonHelper::setDbDateFormat($class['single_schedule']['class_date'], 'd M, Y');
                $response[$key]['held_date_split'] =  CommonHelper::datePartial($class['single_schedule']['class_date']);
                $response[$key]['is_online'] = isset($class['freelance_category']['is_online']) ? $class['freelance_category']['is_online'] : null;
                $response[$key]['online_link'] = $class['single_schedule']['online_link'];
                $response[$key]['image'] = !empty($class['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_images'] . $class['image'] : null;
                $response[$key]['description_video'] = !empty($class['description_video']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_description_video'] . $class['description_video'] : null;
                $response[$key]['description_video_thumbnail'] = !empty($class['description_video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_description_video'] . $class['description_video_thumbnail'] : null;
            }
        }

        return $response;
    }

    public static function searchMultipleClassSchedule($inputs)
    {
        $validation = Validator::make($inputs, ClassValidationHelper::searchMultipleClassScheduleRules()['rules'], ClassValidationHelper::searchMultipleClassScheduleRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);

        $package = Package::getPackageDetail('package_uuid', $inputs['package_uuid'], $inputs, (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));
        if (empty($package)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['no_package_available']);
        }


        $search_classes = Classes::searchClassesByDate('freelancer_id', $inputs['freelancer_id'], $inputs['date']);

        $classes = Classes::getFreelancerUpcomingClasses('freelancer_id', $inputs['freelancer_id'], date('Y-m-d'));

        $response['schedules'] = self::setSearchMultipleClassScheduleResponse($package, $search_classes, $inputs['currency'], $inputs['local_timezone']);

        //        $package_upcoming_classes = Package::getPackageDetailWithUpcomingClasses('package_uuid', $inputs['package_uuid'], $inputs, (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));
        $response['upcoming_schedules'] = self::schedulesDateResponse($classes);
        //        if (empty($response['schedules'])) {
        //            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['no_class_schedule_available']);
        //        }
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function schedulesDateResponse($classes = [])
    {
        $response = [];
        if (!empty($classes)) {
            $index = 0;
            foreach ($classes as $class) {
                if (count($class['schedule']) > 0) {
                    foreach ($class['schedule'] as $schedule) {

                        if ($schedule['status'] == "confirmed") {
                            $response[$index]['class_schedule_uuid'] = $schedule['class_schedule_uuid'];
                            $response[$index]['class_uuid'] = CommonHelper::getClassUuidBYId($schedule['class_id']);
                            $response[$index]['date'] = $schedule['class_date'];
                            $response[$index]['status'] = $schedule['status'];
                            $index++;
                        }
                    }
                }
            }
        }
        return $response;
    }

    public static function setSearchMultipleClassScheduleResponse($package, $classes, $to_currency, $local_timezone = 'UTC')
    {
        $response = [];
        if (!empty($classes)) {
            $sch_key = 0;
            foreach ($classes as $class) {
                if (count($class['schedule']) > 0) {
                    foreach ($class['schedule'] as $schedule) {
                        $from_time_saved_conversion = CommonHelper::convertTimeToTimezone($schedule['from_time'], $schedule['saved_timezone'], $schedule['local_timezone']);
                        $from_time_local_conversion = CommonHelper::convertTimeToTimezone($from_time_saved_conversion, $schedule['local_timezone'], $local_timezone);
                        $to_time_saved_conversion = CommonHelper::convertTimeToTimezone($schedule['to_time'], $schedule['saved_timezone'], $schedule['local_timezone']);
                        $to_time_local_conversion = CommonHelper::convertTimeToTimezone($to_time_saved_conversion, $schedule['local_timezone'], $local_timezone);
                        $response[$sch_key]['class_uuid'] = $class['class_uuid'];
                        $response[$sch_key]['class_schedule_uuid'] = $schedule['class_schedule_uuid'];
                        $response[$sch_key]['freelancer_uuid'] = CommonHelper::getFreelancerUuidByid($class['freelancer_id']);
                        $response[$sch_key]['name'] = $class['name'];
                        $response[$sch_key]['description'] = $class['notes'];
                        $response[$sch_key]['total_students'] = $class['no_of_students'];
                        $enrolled_profiles = ClassResponseHelper::enrolledStudents((!empty($schedule['class_bookings']) ? $schedule['class_bookings'] : []), $schedule['class_schedule_uuid']);
                        $response[$sch_key]['confirm_students'] = count($enrolled_profiles);
                        $response[$sch_key]['members'] = $enrolled_profiles;
                        $response[$sch_key]['price'] = !empty($class['price']) ? (float) CommonHelper::getConvertedCurrency($class['price'], $class['currency'], $to_currency) : 0;
                        $response[$sch_key]['duration'] = (int) CommonHelper::getTimeDifferenceInMinutes($from_time_local_conversion, $to_time_local_conversion);
                        $response[$sch_key]['start_class_date'] = CommonHelper::datePartial($class['start_date']);
                        $response[$sch_key]['end_class_date'] = CommonHelper::datePartial($class['end_date']);
                        $response[$sch_key]['address'] = $class['address'];
                        $response[$sch_key]['lat'] = $class['lat'];
                        $response[$sch_key]['lng'] = $class['lng'];
                        $response[$sch_key]['start_time'] = $from_time_local_conversion;
                        $response[$sch_key]['end_time'] = $to_time_local_conversion;
                        $response[$sch_key]['on_held'] = $schedule['schedule_type'];
                        $response[$sch_key]['held_date'] = CommonHelper::setDbDateFormat($schedule['class_date'], 'd M, Y');
                        $response[$sch_key]['held_date_split'] =  CommonHelper::datePartial($schedule['class_date']);
                        $response[$sch_key]['is_online'] = !empty($schedule['online_link']) ? 1 : 0;
                        $response[$sch_key]['online_link'] = $schedule['online_link'];
                        $response[$sch_key]['image'] = !empty($class['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_images'] . $class['image'] : null;
                        $response[$sch_key]['description_video'] = !empty($class['description_video']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_description_video'] . $class['description_video'] : null;
                        $response[$sch_key]['description_video_thumbnail'] = !empty($class['description_video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_description_video'] . $class['description_video_thumbnail'] : null;
                        $sch_key++;
                    }
                }
            }
        }
        array_values($response);
        return $response;
    }
}
