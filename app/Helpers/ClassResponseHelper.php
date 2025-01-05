<?php

namespace App\Helpers;

use Exception;

Class ClassResponseHelper {
    /*
      |--------------------------------------------------------------------------
      | ClassResponseHelper that contains all the class methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use class processes
      |
     */

    public static function freelancerClassesResponseAsAppointment($data = [], $local_timezone = 'UTC') {
        $response = [];
        if (!empty($data)) {
            $index = 0;
            foreach ($data as $key => $value) {
                if (!empty($value['schedule'])) {
                    foreach ($value['schedule'] as $schedule) {
                        $from_time_saved_conversion = CommonHelper::convertTimeToTimezone($schedule['from_time'], $schedule['saved_timezone'], $schedule['local_timezone']);
                        $from_time_local_conversion = CommonHelper::convertTimeToTimezone($from_time_saved_conversion, $schedule['local_timezone'], $local_timezone);
                        $to_time_saved_conversion = CommonHelper::convertTimeToTimezone($schedule['to_time'], $schedule['saved_timezone'], $schedule['local_timezone']);
                        $to_time_local_conversion = CommonHelper::convertTimeToTimezone($to_time_saved_conversion, $schedule['local_timezone'], $local_timezone);
                        $response[$index]['uuid'] = $value['class_uuid'];
                        $response[$index]['class_schedule_uuid'] = $schedule['class_schedule_uuid'];
                        $response[$index]['title'] = $value['name'];
                        $response[$index]['customer_name'] = null;
                        $response[$index]['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse();
                        $response[$index]['address'] = $value['address'];
                        $response[$index]['lat'] = $value['lat'];
                        $response[$index]['lng'] = $value['lng'];
                        $response[$index]['date'] = $schedule['class_date'];
                        $response[$index]['start_time'] = $from_time_local_conversion;
                        $response[$index]['end_time'] = $to_time_local_conversion;
                        $response[$index]['datetime'] = $schedule['class_date'] . ' ' . $from_time_local_conversion;
                        $response[$index]['price'] = (double) $value['price'];
                        $response[$index]['type'] = 'class';
                        $response[$index]['package_uuid'] = null;
                        $response[$index]['status'] = $schedule['status'];
                        $response[$index]['online_link'] = !empty($value['online_link']) ? $value['online_link'] : null;
                        $index++;
                    }
                }
            }
        }
        return $response;
    }

    public static function upcomingClassesResponseAsAppointment($data = [], $local_timezone = 'UTC', $filter_data = []) {
        $response = [];

        if (!empty($data)) {
            $index = 0;
            $limit_count = 1;

            foreach ($data as $key => $value) {
                if (!empty($value['schedule'])) {
                    foreach ($value['schedule'] as $schedule) {
                        // main logical if statement
                        if (((strtotime($schedule['class_date']) > strtotime(date('Y-m-d'))) || (strtotime($schedule['class_date']) == strtotime(date('Y-m-d')) && strtotime($schedule['from_time']) > strtotime(date('H:i:s')))) && $schedule['status'] != 'cancelled' && $schedule['status'] != 'rejected') {
                            // offset if statement
                            if (!empty($filter_data['offset']) && $index < $filter_data['offset']) {
                                $index++;
                                continue;
                            } else {
                                // limit if statement
                                if (!empty($filter_data['limit']) && $limit_count > $filter_data['limit']) {
                                    $index++;
                                    continue;
                                } else {
                                    $limit_count++;
                                    $from_time_saved_conversion = CommonHelper::convertTimeToTimezone($schedule['from_time'], $schedule['saved_timezone'], $schedule['local_timezone']);
                                    $from_time_local_conversion = CommonHelper::convertTimeToTimezone($from_time_saved_conversion, $schedule['local_timezone'], $local_timezone);
                                    $to_time_saved_conversion = CommonHelper::convertTimeToTimezone($schedule['to_time'], $schedule['saved_timezone'], $schedule['local_timezone']);
                                    $to_time_local_conversion = CommonHelper::convertTimeToTimezone($to_time_saved_conversion, $schedule['local_timezone'], $local_timezone);
                                    $response[$index]['uuid'] = $value['class_uuid'];
                                    $response[$index]['class_schedule_uuid'] = $schedule['class_schedule_uuid'];
                                    $response[$index]['title'] = $value['name'];
                                    $response[$index]['customer_name'] = null;
                                    $response[$index]['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse();
                                    $response[$index]['address'] = $value['address'];
                                    //$response[$index]['date'] = $schedule['class_date'];
                                    $response[$index]['date'] = CommonHelper::convertMyDBDateIntoLocalDate($schedule['start_date_time'], 'UTC', $schedule['local_timezone']);
                                    ;
                                    $response[$index]['start_time'] = $from_time_local_conversion;
                                    $response[$index]['end_time'] = $to_time_local_conversion;
                                    $response[$index]['datetime'] = CommonHelper::convertMyDBDateIntoLocalDate($schedule['start_date_time'], 'UTC', $schedule['local_timezone']) . ' ' . $from_time_local_conversion . ' ' . $from_time_local_conversion;
                                    $response[$index]['price'] = (double) $value['price'];
                                    if (!empty($filter_data['local_currency'])) {
                                        $response[$index]['price'] = !empty($value['price']) ? (double) CommonHelper::getConvertedCurrency($value['price'], $value['currency'], $filter_data['local_currency']) : 0;
                                    }
                                    $response[$index]['class_image'] = !empty($value['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_images'] . $value['image'] : null;
                                    $response[$index]['type'] = 'class';
                                    $response[$index]['status'] = $schedule['status'];
                                    $response[$index]['package_uuid'] = null;
                                    $response[$index]['purchase_time'] = null;
                                    $response[$index]['no_of_students'] = $value['no_of_students'];
                                    $response[$index]['online_link'] = !empty($schedule['online_link']) ? $schedule['online_link'] : null;
                                    $enrolled_profiles = self::enrolledStudents((!empty($schedule['class_bookings']) ? $schedule['class_bookings'] : []), $schedule['class_schedule_uuid']);
                                    $response[$index]['enrolled_students'] = count($enrolled_profiles);
                                    $response[$index]['enrolled_profiles'] = $enrolled_profiles;
                                    $index++;
                                }
                            }
                        }
                    }
                }
            }
        }
        array_values($response);
        return $response;
    }

    public static function freelancerClassesForDahsboardResponseByDate($classes = [], $local_timezone = 'UTC', $status = null) {
        $response = [];
        if (!empty($classes)) {
            $index = 0;

            foreach ($classes as $value) {
                if (!empty($value['schedule'])) {

                    foreach ($value['schedule'] as $schedule) {
                        $enrolled_profiles = self::enrolledStudents((!empty($schedule['class_bookings']) ? $schedule['class_bookings'] : []), $schedule['class_schedule_uuid']);
                        if(count($enrolled_profiles) > 0) {
                            $from_time_saved_conversion = CommonHelper::convertTimeToTimezone($schedule['from_time'], $schedule['saved_timezone'], $schedule['local_timezone']);
                            $from_time_local_conversion = CommonHelper::convertTimeToTimezone($from_time_saved_conversion, $schedule['local_timezone'], $local_timezone);
                            $to_time_saved_conversion = CommonHelper::convertTimeToTimezone($schedule['to_time'], $schedule['saved_timezone'], $schedule['local_timezone']);
                            $to_time_local_conversion = CommonHelper::convertTimeToTimezone($to_time_saved_conversion, $schedule['local_timezone'], $local_timezone);

                            $response[$index]['uuid'] = $value['class_uuid'];
                            $response[$index]['class_schedule_uuid'] = $schedule['class_schedule_uuid'];
                            $response[$index]['title'] = $value['name'];

                            $response[$index]['is_rescheduled_appointment'] = false;
                            $response[$index]['rescheduled_by_uuid'] = null;
                            $response[$index]['rescheduled_by_type'] = null;
                            $response[$index]['class_image'] = !empty($value['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_images'] . $value['image'] : null;
                            $response[$index]['customer_name'] = null;

                            $response[$index]['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse();
                            $response[$index]['address'] = $value['address'];
                            $response[$index]['lat'] = $value['lat'];
                            $response[$index]['lng'] = $value['lng'];

                            $response[$index]['date'] = CommonHelper::convertMyDBDateIntoLocalDate($schedule['start_date_time'], 'UTC', $schedule['local_timezone']);
                            $response[$index]['start_time'] = $from_time_local_conversion;
                            $response[$index]['end_time'] = $to_time_local_conversion;
                            //$response[$index]['datetime'] = $schedule['class_date'] . ' ' . $from_time_local_conversion;
                            $response[$index]['datetime'] = CommonHelper::convertMyDBDateIntoLocalDate($schedule['start_date_time'], 'UTC', $schedule['local_timezone']) . ' ' . $from_time_local_conversion;
                            $response[$index]['price'] = (double) $value['price'];
                            $response[$index]['type'] = 'class';
                            $response[$index]['status'] = $schedule['status'];
                            $response[$index]['package_uuid'] = null;
                            $response[$index]['purchase_time'] = null;

                            $response[$index]['no_of_students'] = $value['no_of_students'];

                            $response[$index]['enrolled_students'] = count($enrolled_profiles);
                            $response[$index]['enrolled_profiles'] = $enrolled_profiles;
                            $response[$index]['online_link'] = !empty($schedule['online_link']) ? $schedule['online_link'] : null;
                            $index++;
                        }
                    }
                }
            }
        }
        return $response;
    }


    public static function freelancerClassesResponseByDate($classes = [], $local_timezone = 'UTC', $status = null) {
        $response = [];
        if (!empty($classes)) {
            $index = 0;

            foreach ($classes as $value) {
                if (!empty($value['schedule'])) {

                    foreach ($value['schedule'] as $schedule) {
                        $enrolled_profiles = self::enrolledStudents((!empty($schedule['class_bookings']) ? $schedule['class_bookings'] : []), $schedule['class_schedule_uuid']);
                        if(count($enrolled_profiles) >= 0) {
                            $from_time_saved_conversion = CommonHelper::convertTimeToTimezone($schedule['from_time'], $schedule['saved_timezone'], $schedule['local_timezone']);
                            $from_time_local_conversion = CommonHelper::convertTimeToTimezone($from_time_saved_conversion, $schedule['local_timezone'], $local_timezone);
                            $to_time_saved_conversion = CommonHelper::convertTimeToTimezone($schedule['to_time'], $schedule['saved_timezone'], $schedule['local_timezone']);
                            $to_time_local_conversion = CommonHelper::convertTimeToTimezone($to_time_saved_conversion, $schedule['local_timezone'], $local_timezone);

                            $response[$index]['uuid'] = $value['class_uuid'];
                            $response[$index]['class_schedule_uuid'] = $schedule['class_schedule_uuid'];
                            $response[$index]['title'] = $value['name'];

                            $response[$index]['is_rescheduled_appointment'] = false;
                            $response[$index]['rescheduled_by_uuid'] = null;
                            $response[$index]['rescheduled_by_type'] = null;
                            $response[$index]['class_image'] = !empty($value['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_images'] . $value['image'] : null;
                            $response[$index]['customer_name'] = null;

                            $response[$index]['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse();
                            $response[$index]['address'] = $value['address'];
                            $response[$index]['lat'] = $value['lat'];
                            $response[$index]['lng'] = $value['lng'];

                            $response[$index]['date'] = CommonHelper::convertMyDBDateIntoLocalDate($schedule['start_date_time'], 'UTC', $schedule['local_timezone']);
                            $response[$index]['start_time'] = $from_time_local_conversion;
                            $response[$index]['end_time'] = $to_time_local_conversion;
                            //$response[$index]['datetime'] = $schedule['class_date'] . ' ' . $from_time_local_conversion;
                            $response[$index]['datetime'] = CommonHelper::convertMyDBDateIntoLocalDate($schedule['start_date_time'], 'UTC', $schedule['local_timezone']) . ' ' . $from_time_local_conversion;
                            $response[$index]['price'] = (double) $value['price'];
                            $response[$index]['type'] = 'class';
                            $response[$index]['status'] = $schedule['status'];
                            $response[$index]['package_uuid'] = null;
                            $response[$index]['purchase_time'] = null;

                            $response[$index]['no_of_students'] = $value['no_of_students'];

                            $response[$index]['enrolled_students'] = count($enrolled_profiles);
                            $response[$index]['enrolled_profiles'] = $enrolled_profiles;
                            $response[$index]['online_link'] = !empty($schedule['online_link']) ? $schedule['online_link'] : null;
                            $index++;
                        }
                    }
                }
            }
        }
        return $response;
    }

    public static function freelancerCalenderClassesResponse($classes = [], $date = null, $local_timezone = 'UTC') {
        $response = [];
        if (!empty($classes)) {
            $index = 0;
            foreach ($classes as $value) {

                if (!empty($value['schedule']) && $value['status'] != 'cancelled' && $value['status'] != 'rejected') {
                    foreach ($value['schedule'] as $schedule) {

                        if (/* $date == $schedule['class_date'] && */ $schedule['status'] != 'cancelled' && $schedule['status'] != 'rejected') {

                            $from_time_saved_conversion = CommonHelper::convertTimeToTimezone($schedule['from_time'], $schedule['saved_timezone'], $schedule['local_timezone']);
                            $from_time_local_conversion = CommonHelper::convertTimeToTimezone($from_time_saved_conversion, $schedule['local_timezone'], $local_timezone);
                            $to_time_saved_conversion = CommonHelper::convertTimeToTimezone($schedule['to_time'], $schedule['saved_timezone'], $schedule['local_timezone']);
                            $to_time_local_conversion = CommonHelper::convertTimeToTimezone($to_time_saved_conversion, $schedule['local_timezone'], $local_timezone);

                            $response[$index]['uuid'] = $value['class_uuid'];
                            $response[$index]['class_schedule_uuid'] = $schedule['class_schedule_uuid'];
                            $response[$index]['title'] = $value['name'];
                            $response[$index]['class_image'] = !empty($value['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_images'] . $value['image'] : null;
                            $response[$index]['customer_name'] = null;
                            $response[$index]['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse();
                            $response[$index]['address'] = $value['address'];
                            $response[$index]['lat'] = $value['lat'];
                            $response[$index]['lng'] = $value['lng'];
                            $response[$index]['date'] = CommonHelper::convertMyDBDateIntoLocalDate( $schedule['start_date_time'],'UTC',$schedule['local_timezone']);
                            $response[$index]['start_time'] = $from_time_local_conversion;
                            $response[$index]['end_time'] = $to_time_local_conversion;
                            //$response[$index]['datetime'] = $schedule['class_date'] . ' ' . $from_time_local_conversion;
                            $response[$index]['datetime'] = CommonHelper::getFullDateAndTime( $schedule['start_date_time'],'UTC',$schedule['local_timezone']);
                            $response[$index]['price'] = (double) $value['price'];
                            $response[$index]['type'] = 'class';
                            $response[$index]['status'] = $schedule['status'];
                            $response[$index]['package_uuid'] = null;
                            $response[$index]['purchase_time'] = null;
                            $response[$index]['online_link'] = !empty($schedule['online_link']) ? $schedule['online_link'] : null;
                            $response[$index]['no_of_students'] = $value['no_of_students'];
                            $enrolled_profiles = self::enrolledStudents((!empty($schedule['class_bookings']) ? $schedule['class_bookings'] : []), $schedule['class_schedule_uuid']);
                            $response[$index]['enrolled_students'] = count($enrolled_profiles);
                            $response[$index]['enrolled_profiles'] = $enrolled_profiles;
                            $index++;
                        }
                    }
                }
            }
        }
        return $response;
    }

    public static function enrolledStudents($bookings = [], $class_schedule_uuid = null) {
        $response = [];
        if (!empty($bookings)) {
            $key = 0;

            foreach ($bookings as $single_booking) {
                $scheduleId = CommonHelper::getRecordByUuid('class_schedules', 'id', $single_booking['class_schedule_id'], 'class_schedule_uuid');
                if ($scheduleId == $class_schedule_uuid) {
                    if ($single_booking['status'] != "cancelled") {
                        $response[$key]['customer_uuid'] = $single_booking['customer']['customer_uuid'];
                        $response[$key]['first_name'] = !empty($single_booking['customer']['first_name']) ? $single_booking['customer']['first_name'] : null;
                        $response[$key]['last_name'] = !empty($single_booking['customer']['last_name']) ? $single_booking['customer']['last_name'] : null;
                        $response[$key]['email'] = $single_booking['customer']['email'];
                        $response[$key]['phone_number'] = $single_booking['customer']['phone_number'];
                        $response[$key]['gender'] = $single_booking['customer']['gender'];
                        $response[$key]['status'] = $single_booking['status'];
                        $response[$key]['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse(!empty($single_booking['customer']['profile_image']) ? $single_booking['customer']['profile_image'] : null);
                        $key++;
                    }
                }
            }
        }

        return $response;
    }

    public static function enrolledStudentsRecords($bookings = [], $class_schedule_uuid = null) {
        $response = [];

        if (!empty($bookings)) {
            $key = 0;

            foreach ($bookings as $booking) {
                if ($booking['status'] != 'cancelled') {
                    $response[$key]['customer_uuid'] = $booking['customer']['customer_uuid'];
                    $response[$key]['first_name'] = !empty($booking['customer']['first_name']) ? $booking['customer']['first_name'] : null;
                    $response[$key]['last_name'] = !empty($booking['customer']['last_name']) ? $booking['customer']['last_name'] : null;
                    $response[$key]['email'] = $booking['customer']['email'];
                    $response[$key]['phone_number'] = $booking['customer']['phone_number'];
                    $response[$key]['gender'] = $booking['customer']['gender'];
                    $response[$key]['status'] = $booking['status'];
                    $response[$key]['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse(!empty($booking['customer']['profile_image']) ? $booking['customer']['profile_image'] : null);
                    $key++;
                }
            }
        }

        return $response;
    }

    public static function classDetailsResponse($data = [], $local_timezone = 'UTC', $inputs = []) {

        $response = [];
        if (!empty($data)) {
            $response['class_uuid'] = $data['class_uuid'];
            $response['name'] = $data['name'];
            $response['no_of_students'] = $data['no_of_students'];
            $response['price'] = !empty($data['price']) ? (double) CommonHelper::getConvertedCurrency($data['price'], $data['currency'], $data['freelancer']['default_currency']) : 0;
            $response['start_date'] = $data['start_date'];
            $response['end_date'] = $data['end_date'];
            $response['image'] = !empty($data['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_images'] . $data['image'] : null;
            $response['description_video'] = !empty($data['description_video']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_description_video'] . $data['description_video'] : null;
            $response['description_video_thumbnail'] = !empty($data['description_video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_description_video'] . $data['description_video_thumbnail'] : null;
            $response['description'] = $data['notes'];
            $response['status'] = $data['status'];
            $response['lat'] = $data['lat'];
            $response['lng'] = $data['lng'];
            $response['address'] = $data['address'];
            if (!empty($data['schedule'])) {
                $response['duration'] = (int) CommonHelper::getTimeDifferenceInMinutes($data['schedule'][0]['from_time'], $data['schedule'][0]['to_time']);
            }
            $response['enrolled_students'] = !empty($data['class_bookings']) ? count($data['class_bookings']) : null;
            $response['online_link'] = !empty($data['online_link']) ? $data['online_link'] : null;
            // $response['service'] = self::classServiceResponse(!empty($data['freelance_category']) ? $data['freelance_category'] : []);
            $response['freelancer'] = self::classFreelancerResponse(!empty($data['freelancer']) ? $data['freelancer'] : [], $inputs);
            $response['schedule'] = self::classScheduleResponse(!empty($data['schedule']) ? $data['schedule'] : [], $local_timezone);
            $response['members'] = self::classMembersResponse(!empty($data['class_bookings']) ? $data['class_bookings'] : [], $inputs);
        }
        return $response;
    }

    public static function classServiceResponse($service = []) {
        $response = null;
        if (!empty($service)) {
            $response['freelancer_category_uuid'] = $service['freelancer_category_uuid'];
            $response['sub_category_uuid'] = CommonHelper::getRecordByUuid('sub_categories', 'id', $service['sub_category_id'], 'sub_category_uuid');
            $response['name'] = $service['name'];
            $response['image'] = !empty($service['sub_category']['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['category_image'] . $service['sub_category']['image'] : null;
            $response['is_online'] = $service['is_online'];
            $response['duration'] = $service['duration'];
            $response['description_video'] = !empty($service['description_video']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_category_video'] . $service['description_video'] : null;
            $response['description_video_thumbnail'] = !empty($service['description_video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_category_video'] . $service['description_video_thumbnail'] : null;
        }
        return $response;
    }

    public static function checkClassPackageInfo($booking_data = [], $logged_in_uuid = null) {
        $package_uuid = null;
        if (!empty($booking_data)) {
            foreach ($booking_data as $booking) {
                if ($booking['customer_id'] == $logged_in_uuid && !empty($booking['package_uuid'])) {
                    $package_uuid = $booking['package_uuid'];
                    break;
                }
            }
        }
        return $package_uuid;
    }

    public static function classMembersResponse($members = [], $inputs = []) {

        $response = [];
        if (!empty($members)) {
            foreach ($members as $key => $customer) {
                if ($customer['status'] != "cancelled") {
                    if (!empty($customer['customer'])) {
                        $chat_data['has_subscription'] = false;
                        $chat_data['has_appointment'] = false;
                        $chat_data['has_followed'] = false;
                        $response[$key]['customer_uuid'] = $customer['customer']['customer_uuid'];
                        $response[$key]['first_name'] = $customer['customer']['first_name'];
                        $response[$key]['last_name'] = $customer['customer']['last_name'];
                        $response[$key]['email'] = $customer['customer']['email'];
                        $response[$key]['phone_number'] = $customer['customer']['phone_number'];
                        $response[$key]['gender'] = $customer['customer']['gender'];
                        $response[$key]['status'] = $customer['status'];
                        $response[$key]['public_chat'] = ($customer['customer']['public_chat'] == 1) ? true : false;

                        if (isset($inputs['login_user_type']) && $inputs['login_user_type'] == "freelancer") {
                            $ids['customer_id'] = $customer['customer']['customer_uuid'];
                            $ids['freelancer_id'] = $inputs['logged_in_uuid'];
                            $chat_data = ClientHelper::getClientChatData($ids);
                        }

                        $response[$key]['has_subscription'] = ($chat_data['has_subscription'] == 1) ? true : false;
                        $response[$key]['has_appointment'] = ($chat_data['has_appointment'] == 1) ? true : false;
                        $response[$key]['has_followed'] = ($chat_data['has_followed'] == 1) ? true : false;
//                    $response[$key]['profile_image'] = config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_image'].$customer['customer']['profile_image'];
                        $response[$key]['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse($customer['customer']['profile_image']);
                    }
                }
            }
        }

        return array_values($response);
    }

    public static function singleDayClassResponse($data = [], $date = null, $inputs = [], $bookings = null) {

        $response = [];
        //$enrolled_profilesRecords = self::enrolledStudents($bookings);

        if (!empty($data['schedule'])) {

            foreach ($data['schedule'] as $key => $schedule) {



                $convertDate = CommonHelper::convertMyDBDateIntoLocalDate($schedule['start_date_time'], 'UTC', $schedule['local_timezone']);

                //TODO:this condition is something seem valid sometime not if we are showing class detail then why this date condition

                if ($convertDate == $date) {


                    $from_time_saved_conversion = CommonHelper::convertTimeToTimezone($schedule['from_time'], $schedule['saved_timezone'], $schedule['local_timezone']);
                    $from_time_local_conversion = CommonHelper::convertTimeToTimezone($from_time_saved_conversion, $schedule['local_timezone'], $inputs['local_timezone']);
                    $to_time_saved_conversion = CommonHelper::convertTimeToTimezone($schedule['to_time'], $schedule['saved_timezone'], $schedule['local_timezone']);
                    $to_time_local_conversion = CommonHelper::convertTimeToTimezone($to_time_saved_conversion, $schedule['local_timezone'], $inputs['local_timezone']);

                    $response['class_uuid'] = $data['class_uuid'];
                    $response['class_schedule_uuid'] = $schedule['class_schedule_uuid'];
                    $response['name'] = $data['name'];
                    $response['no_of_students'] = $data['no_of_students'];
                    $response['price'] = !empty($data['price']) ? (double) CommonHelper::getConvertedCurrency($data['price'], $data['currency'], $inputs['currency']) : 0;
                    $response['image'] = !empty($data['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_images'] . $data['image'] : null;
                    $response['description_video'] = !empty($data['description_video']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_description_video'] . $data['description_video'] : null;
                    $response['description_video_thumbnail'] = !empty($data['description_video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_description_video'] . $data['description_video_thumbnail'] : null;
                    //$response['start_date'] = $data['start_date'];
                    //$response['end_date'] = $data['end_date'];
                    $response['start_date'] = CommonHelper::convertMyDBDateIntoLocalDate($schedule['start_date_time'], 'UTC', $schedule['local_timezone']);
                    $response['end_date'] = CommonHelper::convertMyDBDateIntoLocalDate($schedule['end_date_time'], 'UTC', $schedule['local_timezone']);
                    $response['description'] = $data['notes'];
                    $response['status'] = $schedule['status'];
                    //$response['date'] = $schedule['class_date'];
                    $response['date'] = CommonHelper::convertMyDBDateIntoLocalDate($schedule['start_date_time'], 'UTC', $schedule['local_timezone']);

                    $response['description'] = $data['notes'];
                    $response['status'] = $schedule['status'];
                    //$response['date'] = $schedule['class_date'];
                    $response['date'] = CommonHelper::convertMyDBDateIntoLocalDate($schedule['start_date_time'], 'UTC', $schedule['local_timezone']);
                    $response['start_time'] = $from_time_local_conversion;
                    $response['end_time'] = $to_time_local_conversion;
                    $response['duration'] = (int) CommonHelper::getTimeDifferenceInMinutes($from_time_local_conversion, $to_time_local_conversion);
                    $response['schedule_type'] = $schedule['schedule_type'];
                    $response['lat'] = $data['lat'];
                    $response['lng'] = $data['lng'];
                    $response['address'] = $data['address'];
                    $response['package_uuid'] = null;
                    $response['actual_price'] = 0;
                    $response['discounted_price_while_booking'] = 0;
                    $response['discount_amount_while_booking'] = 0;
                    $response['paid_amount'] = 0;

                    $booking_data = self::prepareBookingStatusResponse($schedule);
                    $response['booking_status'] = $booking_data['status'];

                    if (!empty($inputs['logged_in_uuid']) && !empty($data['class_bookings'])) {
                        foreach ($data['class_bookings'] as $single_booking) {
                            if ($single_booking['class_schedule_uuid'] == $schedule['class_schedule_uuid'] && $single_booking['customer_uuid'] == $inputs['logged_in_uuid']) {
                                if ($inputs['login_user_type'] == "customer") {
                                    $response['actual_price'] = $single_booking['actual_price'];
                                    $response['discounted_price_while_booking'] = !empty($single_booking['discounted_price']) ? (double) $single_booking['discounted_price'] : null;
                                    $response['discount_amount_while_booking'] = !empty($single_booking['discount_amount']) ? (double) $single_booking['discount_amount'] : null;
                                    $response['paid_amount'] = (double) $single_booking['paid_amount'];
                                    break;
//                                    $response['actual_price'] = (double) CommonHelper::getConvertedCurrency($single_booking['actual_price'], $data['currency'], $inputs['currency']);
//                                    $response['discounted_price_while_booking'] = !empty($single_booking['discounted_price']) ? (double) CommonHelper::getConvertedCurrency($single_booking['discounted_price'], $data['currency'], $inputs['currency']) : null;
//                                    $response['discount_amount_while_booking'] = !empty($single_booking['discount_amount']) ? (double) CommonHelper::getConvertedCurrency($single_booking['discount_amount'], $data['currency'], $inputs['currency']) : null;
//                                    $response['paid_amount'] = (double) CommonHelper::getConvertedCurrency($single_booking['paid_amount'], $data['currency'], $inputs['currency']);
//                                    break;
                                } elseif ($inputs['login_user_type'] == "freelancer") {
                                    $response['actual_price'] = (double) CommonHelper::getConvertedCurrency($single_booking['actual_price'], $data['currency'], $inputs['currency']);
                                    $response['discounted_price_while_booking'] = !empty($single_booking['discounted_price']) ? (double) CommonHelper::getConvertedCurrency($single_booking['discounted_price'], $data['currency'], $inputs['currency']) : null;
                                    $response['discount_amount_while_booking'] = !empty($single_booking['discount_amount']) ? (double) CommonHelper::getConvertedCurrency($single_booking['discount_amount'], $data['currency'], $inputs['currency']) : null;
                                    $response['paid_amount'] = (double) CommonHelper::getConvertedCurrency($single_booking['paid_amount'], $data['currency'], $inputs['currency']);
                                    break;
                                }
                            }
                        }
                    }
                    if (!empty($inputs['logged_in_uuid']) && !empty($data['schedule'][0]['class_bookings'])) {
                        $response['package_uuid'] = self::checkClassPackageInfo($data['schedule'][0]['class_bookings'], $inputs['logged_in_uuid']);
                    }
                    $enrolled_profiles = self::enrolledStudents($bookings, $schedule['class_schedule_uuid']);
                    //$enrolled_profiles = $enrolled_profilesRecords;

                    $response['enrolled_students'] = count($enrolled_profiles);
                    $response['enrolled_profiles'] = $enrolled_profiles;
                    $response['service'] = self::classServiceResponse(!empty($data['freelance_category']) ? $data['freelance_category'] : []);
                    $response['freelancer'] = self::classFreelancerResponse(!empty($data['freelancer']) ? $data['freelancer'] : [], $inputs);
                    $response['members'] = self::classMembersResponse(!empty($schedule['class_bookings']) ? $schedule['class_bookings'] : [], $inputs);
                    $response['online_link'] = !empty($booking_data['online_link']) ? $booking_data['online_link'] : $schedule['online_link'];
//                    $response['online_link'] = $schedule['status'] == 'cancelled' ? 'online video link' : $schedule['online_link'];
                }
            }
        }

        return $response;
    }

    public static function singleDayClassResponseRecords($data = [], $date = null, $inputs = []) {

        $response = [];
        $bookings = (isset($data['schedule'][0]['class_bookings'])) ? $data['schedule'][0]['class_bookings'] : [];
        $enrolled_profilesRecords = self::enrolledStudentsRecords($bookings);

        if (!empty($data['schedule'])) {

            foreach ($data['schedule'] as $key => $schedule) {

                // $convertDate = CommonHelper::convertMyDBDateIntoLocalDate($schedule['start_date_time'], 'UTC', $schedule['local_timezone']);
                //TODO:this condition is something seem valid sometime not if we are showing class detail then why this date condition
                //INFO:this date is for to check if one class have multiple schedules this date check show only specific this date slot
                // INFO:Finally kick off the date condition from this code when because of adding the schedule uuid
                //   if ($convertDate == $date) {
                $from_time_saved_conversion = CommonHelper::convertTimeToTimezone($schedule['from_time'], $schedule['saved_timezone'], $schedule['local_timezone']);
                $from_time_local_conversion = CommonHelper::convertTimeToTimezone($from_time_saved_conversion, $schedule['local_timezone'], $inputs['local_timezone']);
                $to_time_saved_conversion = CommonHelper::convertTimeToTimezone($schedule['to_time'], $schedule['saved_timezone'], $schedule['local_timezone']);
                $to_time_local_conversion = CommonHelper::convertTimeToTimezone($to_time_saved_conversion, $schedule['local_timezone'], $inputs['local_timezone']);

                $response['class_uuid'] = $data['class_uuid'];
                $response['class_schedule_uuid'] = $schedule['class_schedule_uuid'];
                $response['name'] = $data['name'];
                $response['no_of_students'] = $data['no_of_students'];
                $response['price'] = !empty($data['price']) ? (double) CommonHelper::getConvertedCurrency($data['price'], $data['currency'], $inputs['currency']) : 0;
                $response['image'] = !empty($data['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_images'] . $data['image'] : null;
                $response['description_video'] = !empty($data['description_video']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_description_video'] . $data['description_video'] : null;
                $response['description_video_thumbnail'] = !empty($data['description_video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_description_video'] . $data['description_video_thumbnail'] : null;
                //$response['start_date'] = $data['start_date'];
                //$response['end_date'] = $data['end_date'];
                $response['start_date'] = CommonHelper::convertMyDBDateIntoLocalDate($schedule['start_date_time'], 'UTC', $schedule['local_timezone']);
                $response['end_date'] = CommonHelper::convertMyDBDateIntoLocalDate($schedule['end_date_time'], 'UTC', $schedule['local_timezone']);
                $response['description'] = $data['notes'];
                $response['status'] = $schedule['status'];
                //$response['date'] = $schedule['class_date'];
                $response['date'] = CommonHelper::convertMyDBDateIntoLocalDate($schedule['start_date_time'], 'UTC', $schedule['local_timezone']);

                $response['description'] = $data['notes'];
                $response['status'] = $schedule['status'];
                //$response['date'] = $schedule['class_date'];
                $response['date'] = CommonHelper::convertMyDBDateIntoLocalDate($schedule['start_date_time'], 'UTC', $schedule['local_timezone']);
                $response['start_time'] = $from_time_local_conversion;
                $response['end_time'] = $to_time_local_conversion;
                $response['duration'] = (int) CommonHelper::getTimeDifferenceInMinutes($from_time_local_conversion, $to_time_local_conversion);
                $response['schedule_type'] = $schedule['schedule_type'];
                $response['lat'] = $data['lat'];
                $response['lng'] = $data['lng'];
                $response['address'] = $data['address'];
                $response['package_uuid'] = null;
                $response['actual_price'] = 0;
                $response['discounted_price_while_booking'] = 0;
                $response['discount_amount_while_booking'] = 0;
                $response['paid_amount'] = 0;

                $booking_data = self::prepareBookingStatusResponse($schedule);
                $response['booking_status'] = $booking_data['status'];
                if (empty($inputs['class_booking_uuid'])) {
                    if (!empty($inputs['logged_in_uuid']) && !empty($data['class_bookings'])) {
                        foreach ($data['class_bookings'] as $single_booking) {

                            if ($single_booking['class_schedule_id'] == $schedule['id'] && $single_booking['customer_id'] == $inputs['profile_id']) {
                                if ($inputs['login_user_type'] == "customer") {
                                    $response['actual_price'] = $single_booking['actual_price'];
                                    $response['discounted_price_while_booking'] = !empty($single_booking['discounted_price']) ? (double) $single_booking['discounted_price'] : null;
                                    $response['discount_amount_while_booking'] = !empty($single_booking['discount_amount']) ? (double) $single_booking['discount_amount'] : null;
                                    $response['paid_amount'] = (double) $single_booking['paid_amount'];
                                    break;
//                                    $response['actual_price'] = (double) CommonHelper::getConvertedCurrency($single_booking['actual_price'], $data['currency'], $inputs['currency']);
//                                    $response['discounted_price_while_booking'] = !empty($single_booking['discounted_price']) ? (double) CommonHelper::getConvertedCurrency($single_booking['discounted_price'], $data['currency'], $inputs['currency']) : null;
//                                    $response['discount_amount_while_booking'] = !empty($single_booking['discount_amount']) ? (double) CommonHelper::getConvertedCurrency($single_booking['discount_amount'], $data['currency'], $inputs['currency']) : null;
//                                    $response['paid_amount'] = (double) CommonHelper::getConvertedCurrency($single_booking['paid_amount'], $data['currency'], $inputs['currency']);
//                                    break;
                                } elseif ($inputs['login_user_type'] == "freelancer") {
                                    $response['actual_price'] = (double) CommonHelper::getConvertedCurrency($single_booking['actual_price'], $data['currency'], $inputs['currency']);
                                    $response['discounted_price_while_booking'] = !empty($single_booking['discounted_price']) ? (double) CommonHelper::getConvertedCurrency($single_booking['discounted_price'], $data['currency'], $inputs['currency']) : null;
                                    $response['discount_amount_while_booking'] = !empty($single_booking['discount_amount']) ? (double) CommonHelper::getConvertedCurrency($single_booking['discount_amount'], $data['currency'], $inputs['currency']) : null;
                                    $response['paid_amount'] = (double) CommonHelper::getConvertedCurrency($single_booking['paid_amount'], $data['currency'], $inputs['currency']);
                                    break;
                                }
                            }
                        }
                    }
                }
                if (isset($inputs['class_booking_uuid']) && !empty($inputs['class_booking_uuid'])) {
                    $prices = self::prepareClassBookingPrices($inputs, $data);

                    $response['actual_price'] = $prices['actual_price'];
                    $response['discounted_price_while_booking'] = $prices['discounted_price_while_booking'];
                    $response['discount_amount_while_booking'] = $prices['discount_amount_while_booking'];
                    $response['paid_amount'] = $prices['paid_amount'];
                }

                if (!empty($inputs['logged_in_uuid']) && !empty($data['schedule'][0]['class_bookings'])) {
                    $inputs['logged_in_id'] = CommonHelper::getRecordByUserType($inputs['login_user_type'], $inputs['logged_in_uuid']);
                    $response['package_uuid'] = self::checkClassPackageInfo($data['schedule'][0]['class_bookings'], $inputs['logged_in_id']);
                }
                //$enrolled_profiles = self::enrolledStudents($bookings, $schedule['class_schedule_uuid']);
                $enrolled_profiles = $enrolled_profilesRecords;

                $response['enrolled_students'] = count($enrolled_profiles);
                $response['enrolled_profiles'] = $enrolled_profiles;
                $response['service'] = self::classServiceResponse(!empty($data['freelance_category']) ? $data['freelance_category'] : []);

                $response['freelancer'] = self::classFreelancerResponse(!empty($data['freelancer']) ? $data['freelancer'] : [], $inputs);

                $response['members'] = self::classMembersResponse(!empty($schedule['class_bookings']) ? $schedule['class_bookings'] : [], $inputs);

                $response['online_link'] = !empty($booking_data['online_link']) ? $booking_data['online_link'] : $schedule['online_link'];
//                    $response['online_link'] = $schedule['status'] == 'cancelled' ? 'online video link' : $schedule['online_link'];
            }

            return $response;
        }
        else {
            return $data;
        }

        // }


    }

    public static function prepareClassBookingPrices($inputs = [], $data = []) {
        $response = [];
        $single_booking = \App\ClassBooking::where('class_booking_uuid', $inputs['class_booking_uuid'])->first()->toArray();
        if ($inputs['login_user_type'] == "customer") {
            $response['actual_price'] = $single_booking['actual_price'];
            $response['discounted_price_while_booking'] = !empty($single_booking['discounted_price']) ? (double) $single_booking['discounted_price'] : null;
            $response['discount_amount_while_booking'] = !empty($single_booking['discount_amount']) ? (double) $single_booking['discount_amount'] : null;
            $response['paid_amount'] = (double) $single_booking['paid_amount'];
        } elseif ($inputs['login_user_type'] == "freelancer") {
            $response['actual_price'] = (double) CommonHelper::getConvertedCurrency($single_booking['actual_price'], $data['currency'], $inputs['currency']);
            $response['discounted_price_while_booking'] = !empty($single_booking['discounted_price']) ? (double) CommonHelper::getConvertedCurrency($single_booking['discounted_price'], $data['currency'], $inputs['currency']) : null;
            $response['discount_amount_while_booking'] = !empty($single_booking['discount_amount']) ? (double) CommonHelper::getConvertedCurrency($single_booking['discount_amount'], $data['currency'], $inputs['currency']) : null;
            $response['paid_amount'] = (double) CommonHelper::getConvertedCurrency($single_booking['paid_amount'], $data['currency'], $inputs['currency']);
        }
        return $response;
    }

    public static function prepareBookingStatusResponse($schedule) {
        $status = null;
        $online_link = $schedule['online_link'];
        if (!empty($schedule['class_bookings'])) {
            foreach ($schedule['class_bookings'] as $key => $booking) {
                if (!empty($booking)) {
                    $status = $booking['status'];
                    $online_link = ($booking['status'] == "cancelled") ? "online video link" : $schedule['online_link'];
                }
            }
        }
        return ['status' => $status, 'online_link' => $online_link];
    }

    public static function classScheduleResponse($schedule = [], $local_timezone = 'UTC') {
        $response = [];
        foreach ($schedule as $index => $single_class) {
            $from_time_saved_conversion = CommonHelper::convertTimeToTimezone($single_class['from_time'], $single_class['saved_timezone'], $single_class['local_timezone']);
            $from_time_local_conversion = CommonHelper::convertTimeToTimezone($from_time_saved_conversion, $single_class['local_timezone'], $local_timezone);
            $to_time_saved_conversion = CommonHelper::convertTimeToTimezone($single_class['to_time'], $single_class['saved_timezone'], $single_class['local_timezone']);
            $to_time_local_conversion = CommonHelper::convertTimeToTimezone($to_time_saved_conversion, $single_class['local_timezone'], $local_timezone);
            $response[$index]['class_schedule_uuid'] = $single_class['class_schedule_uuid'];
            $response[$index]['date'] = $single_class['class_date'];
            $response[$index]['start_time'] = $from_time_local_conversion;
            $response[$index]['end_time'] = $to_time_local_conversion;
            $response[$index]['schedule_type'] = $single_class['schedule_type'];
            $response[$index]['validity_type'] = $single_class['validity_type'];
            $response[$index]['validity'] = $single_class['validity'];

            $index++;
        }
        return $response;
    }

    public static function schedulesDateResponse($schedule = []) {

        $response = [];
        foreach ($schedule as $index => $single_class) {
            $response[$index]['class_schedule_uuid'] = $single_class['class_schedule_uuid'];
            $response[$index]['class_uuid'] = CommonHelper::getRecordByUuid('classes', 'id', $single_class['class_id'], 'class_uuid');
            $response[$index]['date'] = CommonHelper::convertDateToTimeZone($single_class['class_date'] . ' ' . $single_class['from_time'], $single_class['saved_timezone'], $single_class['local_timezone']);
            $response[$index]['status'] = $single_class['status'];
        }
        return $response;
    }

    public static function classFreelancerResponse($freelancer = [], $inputs = []) {

        $response = [];
        $response['freelancer_uuid'] = $freelancer['freelancer_uuid'];
        $response['first_name'] = $freelancer['first_name'];
        $response['last_name'] = $freelancer['last_name'];
//        $response['profile_image'] = !empty($freelancer['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $freelancer['profile_image'] : null;
        $response['profile_images'] = FreelancerResponseHelper::freelancerProfileImagesResponse($freelancer['profile_image']);
        $response['profession_details'] = LoginHelper::processFreelancerProfessionResponse(!empty($freelancer['profession']) ? $freelancer['profession'] : []);
        if (isset($inputs['login_user_type']) && $inputs['login_user_type'] == "customer") {
            $ids['customer_id'] = CommonHelper::getCutomerIdByUuid($inputs['logged_in_uuid']);
            $ids['freelancer_id'] = $freelancer['id'];
            $chat_data = ClientHelper::getClientChatData($ids);
            $response['has_subscription'] = ($chat_data['has_subscription'] == 1) ? true : false;
            $response['has_appointment'] = ($chat_data['has_appointment'] == 1) ? true : false;
            $response['has_followed'] = ($chat_data['has_followed'] == 1) ? true : false;
        }
        return $response;
    }

    public static function getClassesListResponse($classResponse = [], $local_timezone = 'UTC') {

        $response = [];
        if (!empty($classResponse)) {
            foreach ($classResponse as $key => $class) {

                if (!empty($class['schedule'])) {


                    $response[$key]['class_uuid'] = $class['class_uuid'];
                    $response[$key]['name'] = $class['name'];
                    $response[$key]['no_of_students'] = $class['no_of_students'];
                    $response[$key]['price'] = !empty($class['price']) ? (double) CommonHelper::getConvertedCurrency($class['price'], $class['currency'], $class['freelancer']['default_currency']) : 0;
                    $response[$key]['image'] = !empty($class['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_images'] . $class['image'] : null;
                    $response[$key]['start_date'] = $class['start_date'];
                    $response[$key]['end_date'] = $class['end_date'];
                    $response[$key]['duration'] = null;
                    if (!empty($class['single_schedule'])) {
                        $response[$key]['duration'] = (int) CommonHelper::getTimeDifferenceInMinutes($class['single_schedule']['from_time'], $class['single_schedule']['to_time']);
                    } elseif (!empty($class['schedule'])) {
                        $response[$key]['duration'] = (int) CommonHelper::getTimeDifferenceInMinutes($class['schedule'][0]['from_time'], $class['schedule'][0]['to_time']);
                    }
                    $response[$key]['description'] = $class['notes'];
                    $response[$key]['status'] = $class['status'];
                    $response[$key]['address'] = $class['address'];
                    $response[$key]['online_link'] = !empty($class['online_link']) ? $class['online_link'] : null;
                    $response[$key]['service'] = self::classListServiceResponse(!empty($class['freelance_category']) ? $class['freelance_category'] : []);
                }
            }
        }
        return self::getPureResponce($response);
    }

    public static function getPureResponce($records) {
        $results = [];
        foreach ($records as $item) {
            $results[] = $item;
        }
        return $results;
    }

    public static function getSingleClassResponse($class = [], $local_timezone = 'UTC') {
        $response = [];
        if (!empty($class)) {
            $response['class_uuid'] = $class['class_uuid'];
            $response['name'] = $class['name'];
            $response['no_of_students'] = $class['no_of_students'];
            $response['price'] = !empty($class['price']) ? (double) CommonHelper::getConvertedCurrency($class['price'], $class['currency'], $class['freelancer']['default_currency']) : 0;
            $response['image'] = !empty($class['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_images'] . $class['image'] : null;
            $response['start_date'] = $class['start_date'];
            $response['end_date'] = $class['end_date'];
            $response['duration'] = null;
            if (!empty($class['single_schedule'])) {
                $response['duration'] = (int) CommonHelper::getTimeDifferenceInMinutes($class['single_schedule']['from_time'], $class['single_schedule']['to_time']);
            }
            $response['description'] = $class['notes'];
            $response['status'] = $class['status'];
            $response['address'] = $class['address'];
            $response['online_link'] = !empty($class['online_link']) ? $class['online_link'] : null;
            $response['service'] = self::classListServiceResponse(!empty($class['freelance_category']) ? $class['freelance_category'] : []);
        }
        return $response;
    }

    public static function classListServiceResponse($service = []) {
        $response = null;

        if (!empty($service)) {
            $response['service_uuid'] = $service['freelancer_category_uuid'];
            $response['sub_category_uuid'] = CommonHelper::getRecordByUuid('sub_categories', 'id', $service['sub_category_id'], 'sub_category_uuid');
            $response['name'] = $service['name'];
            $response['price'] = $service['price'];
            $response['duration'] = $service['duration'];
            $response['is_online'] = $service['is_online'];
            $response['image'] = !empty($service['sub_category']['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['category_image'] . $service['sub_category']['image'] : null;
        }
        return $response;
    }

    public static function getAvailableClassesResponse($classData, $to_currency, $local_timezone = 'UTC') {
        $response = [];

        if (!empty($classData)) {

            foreach ($classData as $key => $data) {
                // $response[$key]['freelancer_category_uuid'] = $data['freelance_category']['freelancer_category_uuid'];
                // $response[$key]['category_uuid'] = !empty($data['freelance_category']['category']) ? $data['freelance_category']['category']['category_uuid'] : null;
                // $response[$key]['sub_category_uuid'] = !empty($data['freelance_category']['sub_category']) ? $data['freelance_category']['sub_category']['sub_category_uuid'] : null;
                $response[$key]['class_uuid'] = $data['class_uuid'];
                $response[$key]['name'] = $data['name'];

                $response[$key]['description'] = $data['notes'];
                // $response[$key]['base_category_name'] = $data['freelance_category']['category']['name'];
                $response[$key]['price'] = !empty($data['price']) ? (double) CommonHelper::getConvertedCurrencyExtended($data['price'], $data['currency'], $to_currency) : 0;
                // $response[$key]['duration'] = $data['freelance_category']['duration'];
                if (!empty($data['schedule'])) {
                    $response[$key]['duration'] = (int) CommonHelper::getTimeDifferenceInMinutes($data['schedule'][0]['from_time'], $data['schedule'][0]['to_time']);
                }

                $response[$key]['is_online'] = !empty($data['online_link']) ? 1 : 0;
                $response[$key]['online_link'] = !empty($data['online_link']) ? $data['online_link'] : null;
                $response[$key]['image'] = null;
                if (!empty($data['image'])) {
                    $response[$key]['image'] = !empty($data['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_images'] . $data['image'] : null;
                } else
                {   $response[$key]['image'] = null;
                    //if (!empty($data['freelance_category']['category']['image'])) {
                    // $response[$key]['image'] = !empty($data['freelance_category']['category']['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['category_image'] . $data['freelance_category']['category']['image'] : null;
                }

                $response[$key]['description_video'] = !empty($data['description_video']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_description_video'] . $data['description_video'] : null;
                $response[$key]['description_video_thumbnail'] = !empty($data['description_video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_description_video'] . $data['description_video_thumbnail'] : null;
                $response[$key]['upcoming_schedules'] = self::schedulesDateResponse(!empty($data['schedule']) ? $data['schedule'] : []);

            }
        }

        return $response;
    }

}

?>
