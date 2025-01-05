<?php

namespace App\Helpers;

Class CalenderResponseHelper {
    /*
      |--------------------------------------------------------------------------
      | CalenderResponseHelper that contains all the calender methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use calender processes
      |
     */

    public static function blockedTimingsResponseAsAppointment($data = [], $query_date = null, $local_timezone = 'UTC') {
        $response = [];
        if (!empty($data)) {
            $index = 0;
            foreach ($data as $value) {
                if (!empty($query_date) && ($query_date >= $value['start_date'] && $query_date <= $value['end_date'])) {
                    $from_time_saved_conversion = CommonHelper::convertTimeToTimezone($value['from_time'], $value['saved_timezone'], $value['local_timezone']);
                    $from_time_local_conversion = CommonHelper::convertTimeToTimezone($from_time_saved_conversion, $value['local_timezone'], $local_timezone);
                    $to_time_saved_conversion = CommonHelper::convertTimeToTimezone($value['to_time'], $value['saved_timezone'], $value['local_timezone']);
                    $to_time_local_conversion = CommonHelper::convertTimeToTimezone($to_time_saved_conversion, $value['local_timezone'], $local_timezone);
                    $response[$index]['uuid'] = $value['blocked_time_uuid'];
                    $response[$index]['title'] = null;
                    $response[$index]['customer_name'] = null;
//                    $response[$index]['profile_image'] = null;
                    $response[$index]['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse();
                    $response[$index]['address'] = null;
                    $response[$index]['lat'] = null;
                    $response[$index]['lng'] = null;
                    $response[$index]['date'] = $query_date;
                    $response[$index]['start_time'] = $from_time_local_conversion;
                    $response[$index]['end_time'] = $to_time_local_conversion;
                    $response[$index]['datetime'] = $query_date . ' ' . $from_time_local_conversion;
                    $response[$index]['price'] = null;
                    $response[$index]['type'] = 'blockedTime';
                    $response[$index]['status'] = null;
                    $index++;
                }
            }
        }
        return $response;
    }

    public static function blockedTimingsResponse($data = [], $local_timezone = 'UTC') {
        $response = [];
        if (!empty($data)) {
            foreach ($data as $index => $value) {
                $from_time_saved_conversion = CommonHelper::convertTimeToTimezone($value['from_time'], $value['saved_timezone'], $value['local_timezone']);
                $from_time_local_conversion = CommonHelper::convertTimeToTimezone($from_time_saved_conversion, $value['local_timezone'], $local_timezone);
                $to_time_saved_conversion = CommonHelper::convertTimeToTimezone($value['to_time'], $value['saved_timezone'], $value['local_timezone']);
                $to_time_local_conversion = CommonHelper::convertTimeToTimezone($to_time_saved_conversion, $value['local_timezone'], $local_timezone);
                $response[$index]['blocked_time_uuid'] = $value['blocked_time_uuid'];
                $response[$index]['start_date'] = $value['start_date'];
                $response[$index]['end_date'] = $value['end_date'];
                $response[$index]['start_time'] = $from_time_local_conversion;
                $response[$index]['end_time'] = $to_time_local_conversion;
            }
        }
        return $response;
    }

}

?>