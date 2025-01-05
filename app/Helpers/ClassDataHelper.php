<?php

namespace App\Helpers;

use App\Http\Controllers\Auth\ConfirmPasswordController;

Class ClassDataHelper {
    /*
      |--------------------------------------------------------------------------
      | ClassDataHelper that contains all the class data methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use class processes
      |
     */

    public static function makeFreelancerClassArray($input) {

        $data['class_uuid'] = UuidHelper::generateUniqueUUID('classes', 'class_uuid');
        $data['freelancer_id'] = !empty($input['freelancer_id']) ? $input['freelancer_id'] : null;
        $data['service_id'] = !empty($input['service_uuid']) ? CommonHelper::getRecordByUuid('freelancer_categories','freelancer_category_uuid',$input['service_uuid'],'id') : null;
        $data['name'] = !empty($input['name']) ? $input['name'] : null;
        $data['no_of_students'] = !empty($input['no_of_students']) ? $input['no_of_students'] : null;
        $data['price'] = !empty($input['price']) ? $input['price'] : null;
        $data['currency'] = !empty($input['currency']) ? $input['currency'] : null;
        $data['notes'] = !empty($input['notes']) ? $input['notes'] : null;
        $data['start_date'] = !empty($input['start_date']) ? $input['start_date'] : "0000-00-00";
        $data['end_date'] = !empty($input['end_date']) ? $input['end_date'] : "0000-00-00";
        $data['address'] = !empty($input['address']) ? $input['address'] : null;
        $data['lat'] = !empty($input['lat']) ? $input['lat'] : null;
        $data['lng'] = !empty($input['lng']) ? $input['lng'] : null;
        $data['image'] = !empty($input['image']) ? $input['image'] : null;
        $data['description_video'] = !empty($input['description_video']) ? $input['description_video'] : null;
        $data['description_video_thumbnail'] = !empty($input['description_video_thumbnail']) ? $input['description_video_thumbnail'] : null;
        $data['online_link'] = !empty($input['online_link']) ? $input['online_link'] : null;

        return $data;
    }

    public static function makeFreelancerUpdateClassArray($class, $inputs) {
        $data = ['success' => true, 'message' => 'Successful request', 'data' => []];
        if (array_key_exists('freelancer_uuid', $inputs) && !empty($inputs['freelancer_uuid'])) {
            $data['data']['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);
        }
//        if (array_key_exists('service_uuid', $inputs) && !empty($inputs['service_uuid'])) {
//            $data['data']['service_uuid'] = $inputs['service_uuid'];
//        }
        if (array_key_exists('name', $inputs) && !empty($inputs['name'])) {
            $data['data']['name'] = $inputs['name'];
        }
        if (array_key_exists('online_link', $inputs) && !empty($inputs['online_link'])) {
            $data['data']['online_link'] = $inputs['online_link'];
        }
        if (array_key_exists('notes', $inputs) && !empty($inputs['notes'])) {
            $data['data']['notes'] = $inputs['notes'];
        }
        if (array_key_exists('no_of_students', $inputs) && !empty($inputs['no_of_students'])) {
            if ($class['no_of_students'] != $inputs['no_of_students']) {
                $check_existing_booking = ClassHelper::checkClassBookingExists($class, $inputs);
                if (!$check_existing_booking['success']) {
                    $data['success'] = false;
                    $data['message'] = ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['no_of_student_update_error'];
                    return $data;
                }
            }
            $data['data']['no_of_students'] = $inputs['no_of_students'];
        }
        if (array_key_exists('price', $inputs) && !empty($inputs['price'])) {
            if ($class['price'] != $inputs['price']) {
                $check_existing_booking = ClassHelper::checkClassBookingExists($class, $inputs);
                if (!$check_existing_booking['success']) {
                    $data['success'] = false;
                    $data['message'] = ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['class_price_update_error'];
                    return $data;
                }
            }
            $data['data']['price'] = $inputs['price'];
        }
//        if (array_key_exists('start_date', $inputs) && !empty($inputs['start_date'])) {
//            $data['data']['start_date'] = $inputs['start_date'];
//        }
//        if (array_key_exists('end_date', $inputs) && !empty($inputs['end_date'])) {
//            $data['data']['end_date'] = $inputs['end_date'];
//        }
        if (array_key_exists('address', $inputs) && !empty($inputs['address'])) {
            if ($class['address'] != $inputs['address']) {
                $check_existing_booking = ClassHelper::checkClassBookingExists($class, $inputs);
                if (!$check_existing_booking['success']) {
                    $data['success'] = false;
                    $data['message'] = ClassValidationHelper::changeClassStatusRules()['message_' . strtolower($inputs['lang'])]['address_update_error'];
                    return $data;
                }
            }
            $data['data']['address'] = $inputs['address'];
            if (array_key_exists('lat', $inputs) && !empty($inputs['lat'])) {
                $data['data']['lat'] = $inputs['lat'];
            }
            if (array_key_exists('lng', $inputs) && !empty($inputs['lng'])) {
                $data['data']['lng'] = $inputs['lng'];
            }
        }
        return $data;
    }

}

?>
