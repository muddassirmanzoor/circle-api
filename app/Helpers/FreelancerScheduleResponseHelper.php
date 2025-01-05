<?php

namespace App\Helpers;

Class FreelancerScheduleResponseHelper {
    /*
      |--------------------------------------------------------------------------
      | FreelancerScheduleResponseHelper that contains schedule related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use schedule processes
      |
     */

    /**
     * Description of FreelancerScheduleResponseHelper
     *
     * @author ILSA Interactive
     */
    public static function freelancerScheduleResponse($data = [], $local_timezone = 'UTC') {

        $response = [];
        if (!empty($data)) {
            $day_inputs = [];
            $index = 0;
            foreach ($data as $value) {
                if (!in_array(strtolower($value['day']), $day_inputs)) {
                    $response[$index]['day'] = strtolower($value['day']);
                    $response[$index]['saved_timezone'] = $value['local_timezone'];
                    $response[$index]['timings'] = self::freelancerDayScheduleResponse($data, strtolower($value['day']), $local_timezone);
                    $index++;
                    array_push($day_inputs, strtolower($value['day']));
                }
            }
        }
        return $response;
    }

    public static function freelancerDayScheduleResponse($data, $day = null, $local_timezone = 'UTC') {
        $response = [];
        $index = 0;

        foreach ($data as $value) {
            if (strtolower($value['day']) == $day) {
                $from_time_saved_conversion = CommonHelper::convertTimeToTimezone($value['from_time'], $value['saved_timezone'], $value['local_timezone']);

                $from_time_local_conversion = CommonHelper::convertTimeToTimezone($from_time_saved_conversion, $value['local_timezone'], $local_timezone);

                $to_time_saved_conversion = CommonHelper::convertTimeToTimezone($value['to_time'], $value['saved_timezone'], $value['local_timezone']);

                $to_time_local_conversion = CommonHelper::convertTimeToTimezone($to_time_saved_conversion, $value['local_timezone'], $local_timezone);
                $response[$index]['schedule_uuid'] = $value['schedule_uuid'];
                $response[$index]['from_time'] = $from_time_local_conversion;
                $response[$index]['to_time'] = $to_time_local_conversion;
                $index++;
            }
        }
        return $response;
    }

}

?>
