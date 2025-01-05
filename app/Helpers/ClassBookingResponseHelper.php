<?php

namespace App\Helpers;

Class ClassBookingResponseHelper {
    /*
      |--------------------------------------------------------------------------
      | ClassBookingResponseHelper that contains all the post response methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use post processes
      |
     */

    public static function classBookingResponse($data = []) {
        $response = [];
        if (!empty($data)) {
            $response['class_booking_uuid'] = $data['class_booking_uuid'];
            $response['class_uuid'] = CommonHelper::getRecordByUuid('classes','id',$data['class_id'],'class_uuid');
            $response['class_schedule_uuid'] = CommonHelper::getRecordByUuid('class_schedules','id',$data['class_schedule_id'],'class_schedule_uuid');
            $response['transaction_id'] = 'kjljlljljjl';
           // $response['actual_amount'] = $data['save_transaction']['actual_amount'];
            $response['actual_amount'] = $data['actual_amount'];
            $response['total_amount'] = $data['total_amount'];
            //$response['commission_rate'] = $data['save_transaction']['commission_rate'];
            $response['status'] = $data['status'];
        }
        return $response;
    }

}
