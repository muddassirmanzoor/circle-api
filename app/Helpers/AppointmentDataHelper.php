<?php

namespace App\Helpers;

Class AppointmentDataHelper {
    /*
      |--------------------------------------------------------------------------
      | AppointmentDataHelper that contains appointment related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use appointment processes
      |
     */

    /**
     * Description of AppointmentDataHelper
     *
     * @author ILSA Interactive
     */
    public static function makeFreelancerAppointmentArray($inputs) {
        // $walkin_customer = AppointmentHelper::checkIsCustomerWalkIn($inputs['customer_uuid']);
        $inputs['schedule_count'] = (isset($inputs['schedule_count']) && !empty($inputs['schedule_count'])) ? $inputs['schedule_count'] : 1;
        $data = array(
            'appointment_uuid' => UuidHelper::generateUniqueUUID('appointments', 'appointment_uuid'),
            'customer_id' => !empty($inputs['customer_uuid']) ? CommonHelper::getRecordByUuid('customers', 'customer_uuid', $inputs['customer_uuid']) : null,
            'freelancer_id' => !empty($inputs['freelancer_uuid']) ? CommonHelper::getRecordByUuid('freelancers', 'freelancer_uuid', $inputs['freelancer_uuid']) : null,
            'appointment_start_date_time' => !empty($inputs['date']) ? strtotime($inputs['date'] . ' ' . $inputs['start_time']) : null,
            'appointment_end_date_time' => !empty($inputs['date']) ? strtotime($inputs['date'] . ' ' . $inputs['end_time']) : null,
            'appointment_date' => !empty($inputs['date']) ? $inputs['date'] : null,
            'from_time' => !empty($inputs['start_time']) ? $inputs['start_time'] : null,
            'to_time' => !empty($inputs['end_time']) ? $inputs['end_time'] : null,
            'address' => !empty($inputs['address']) ? $inputs['address'] : null,
            'lat' => !empty($inputs['lat']) ? $inputs['lat'] : null,
            'lng' => !empty($inputs['lng']) ? $inputs['lng'] : null,
            'travelling_distance' => !empty($inputs['travelling_distance']) ? $inputs['travelling_distance'] : null,
            'promocode_id' => !empty($inputs['promocode_uuid']) ? $inputs['promocode_uuid'] : null,
            'price' => !empty($inputs['actual_price']) ? $inputs['actual_price'] / $inputs['schedule_count'] : 0.0,
            'package_paid_amount' => isset($inputs['package_paid_amount']) ? $inputs['package_paid_amount'] : null,
            'discounted_price' => !empty($inputs['discounted_price']) ? $inputs['discounted_price'] / $inputs['schedule_count'] : null,
            'travelling_charges' => !empty($inputs['travelling_charges']) ? $inputs['travelling_charges'] : null,
            'paid_amount' => !empty($inputs['paid_amount']) ? $inputs['paid_amount'] / $inputs['schedule_count'] : 0.0,
            'currency' => !empty($inputs['currency']) ? $inputs['currency'] : null,
            'title' => !empty($inputs['title']) ? $inputs['title'] : null,
            'notes' => !empty($inputs['notes']) ? $inputs['notes'] : null,
            
            //'service_id' => !empty($inputs['service_uuid']) ? $inputs['service_uuid'] : null,
            'service_id' => !empty($inputs['service_uuid']) ? CommonHelper::getRecordByUuid('freelancer_categories', 'freelancer_category_uuid', $inputs['service_uuid'], 'id') : null,
            'logged_in_uuid' => !empty($inputs['logged_in_uuid']) ? $inputs['logged_in_uuid'] : null,
            'saved_timezone' => "UTC",
            'local_timezone' => !empty($inputs['local_timezone']) ? $inputs['local_timezone'] : null,
            'online_link' => !empty($inputs['online_link']) ? $inputs['online_link'] : null,
            //TODO::i hava to understand this make sure these two amouts are pass to in right form because this is confustion
            'discount' => !empty($inputs['discount_amount']) ? $inputs['discount_amount'] / $inputs['schedule_count'] : null,
//            'discount' => !empty($inputs['discount']) ? $inputs['discount'] : null,
            'is_online' => !empty($inputs['is_online']) ? $inputs['is_online'] : 0,
            'transaction_id' => !empty($inputs['transaction_id']) ? $inputs['transaction_id'] : null,
            'package_id' => !empty($inputs['package_uuid']) ? CommonHelper::getRecordByUuid('packages', 'package_uuid', $inputs['package_uuid']) : null,
            'session_number' => !empty($inputs['session_number']) ? $inputs['session_number'] : 1,
            'total_session' => !empty($inputs['total_session']) ? $inputs['total_session'] : 1,
            'created_by' => isset($inputs['login_user_type']) && $inputs['login_user_type'] == 'freelancer' ? 'freelancer' : 'customer'
        );
//        if ($walkin_customer){
//            $data['status'] = 'confirmed';
//        }
        return $data;
    }

    public static function processAppointmentLocationType($inputs) {
        $location_type = null;
        if (!empty($inputs['location_type'])) {
            $location_type = strtolower($inputs['location_type']);
        }
        return $location_type;
//        if ($inputs['login_user_type'] == 'freelancer') {
//            $location_type = 'freelancer';
//        } elseif (!empty($inputs['location_type'])) {
//            if ($inputs['location_type'] == 'freelancer location' || $inputs['location_type'] == 'class location' || $inputs['location_type'] == 'partner gym location') {
//                $location_type = 'freelancer';
//            } elseif ($inputs['location_type'] == 'my location') {
//                $location_type = 'customer';
//            } elseif ($inputs['location_type'] == 'drop-pin location') {
//                $location_type = 'shared';
//            }
//        }
//        return $location_type;
    }

    public static function updateFreelancerAppointmentArray($inputs) {
        $data = array(
//            'appointment_uuid' => UuidHelper::generateUniqueUUID('appointments', 'appointment_uuid'),
            'appointment_uuid' => !empty($inputs['appointment_uuid']) ? $inputs['appointment_uuid'] : null,
            'freelancer_id' => !empty($inputs['freelancer_uuid']) ? CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']) : null,
//            'customer_uuid' => !empty($inputs['customer_uuid']) ? $inputs['customer_uuid'] : null,
            'appointment_date' => !empty($inputs['date']) ? $inputs['date'] : null,
            'from_time' => !empty($inputs['start_time']) ? $inputs['start_time'] : null,
            'to_time' => !empty($inputs['end_time']) ? $inputs['end_time'] : null,
            'appointment_start_date_time' => !empty($inputs['date']) ? strtotime($inputs['date'] . ' ' . $inputs['start_time']) : null,
            'appointment_end_date_time' => !empty($inputs['date']) ? strtotime($inputs['date'] . ' ' . $inputs['end_time']) : null,
//            'address' => !empty($inputs['address']) ? $inputs['address'] : null,
//            'lat' => !empty($inputs['lat']) ? $inputs['lat'] : null,
//            'lng' => !empty($inputs['lng']) ? $inputs['lng'] : null,
//            'price' => !empty($inputs['price']) ? $inputs['price'] : 0.00,
//            'title' => !empty($inputs['title']) ? $inputs['title'] : null,
           
            'local_timezone' => !empty($inputs['local_timezone']) ? $inputs['local_timezone'] : null,
//            'service_uuid' => !empty($inputs['service_uuid']) ? $inputs['service_uuid'] : null,
            'currency' => !empty($inputs['currency']) ? $inputs['currency'] : null,
        );
        if(!empty($inputs['notes']))
        {
            $data['notes'] = $inputs['notes'];
        }
        if(!empty($inputs['freelancer_notes']))
        {
            $data['freelancer_notes'] = $inputs['freelancer_notes'];
        }
        return $data;
    }

    public static function makeFreelancerAppointmentServicesArray($inputs, $appointment_uuid) {
        $data = [];
//        foreach ($data['services'] as $key => $row) {
//            $data[$key] = array(
//                'appointment_service_uuid' => UuidHelper::generateUniqueUUID(),
//                'appointment_uuid' => $appointment_uuid,
//                'service_uuid' => !empty($row['service_uuid']) ? $row['service_uuid'] : null,
//            );
//        }
        $data[0]['appointment_service_uuid'] = UuidHelper::generateUniqueUUID();
        $data[0]['appointment_uuid'] = $appointment_uuid;
        $data[0]['service_uuid'] = $inputs['service_uuid'];
        return $data;
    }

}

?>
