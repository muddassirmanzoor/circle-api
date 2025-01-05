<?php

namespace App\Helpers;

Class FreelancerDataHelper {
    /*
      |--------------------------------------------------------------------------
      | FreelancerDataHelper that contains all the Freelancer data methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use Freelancer processes
      |
     */

    public static function makeFreelancerCategoryArray($inputs = []) {
        $data = [];
        if (!empty($inputs['sub_categories'])) {
            foreach ($inputs['sub_categories'] as $key => $category) {
                $data[$key]['freelancer_category_uuid'] = UuidHelper::generateUniqueUUID();
                $data[$key]['freelancer_id'] = CommonHelper::getFreelancerIdByUuid( $inputs['freelancer_uuid']);
                $data[$key]['category_id'] = CommonHelper::getCategoryIdByUuid( $inputs['category_uuid']);
                $data[$key]['sub_category_id'] = CommonHelper::getSubCategoryIdByUuid($category['sub_category_uuid']);
                $data[$key]['name'] = !empty($category['name']) ? $category['name'] : null;
                $data[$key]['price'] = !empty($category['price']) ? (double) $category['price'] : null;
                $data[$key]['duration'] = !empty($category['duration']) ? $category['duration'] : null;
                $data[$key]['is_online'] = !empty($category['is_online']) ? $category['is_online'] : 0;
                $data[$key]['is_archive'] = 0;
            }
        }
        return $data;
    }

    public static function makeFreelancerBlockTimeArray($input) {
        $data = [
            'blocked_time_uuid' => UuidHelper::generateUniqueUUID(),
            'freelancer_id' => !empty($input['freelancer_uuid']) ? CommonHelper::getFreelancerIdByUuid($input['freelancer_uuid']) : null,
            'start_date' => !empty($input['start_date']) ? $input['start_date'] : "0000-00-00",
            'end_date' => !empty($input['end_date']) ? $input['end_date'] : "0000-00-00",
            'from_time' => !empty($input['start_time']) ? $input['start_time'] : null,
            'to_time' => !empty($input['end_time']) ? $input['end_time'] : null,
            'notes' => !empty($input['notes']) ? $input['notes'] : null,
            'saved_timezone' => "UTC",
            'local_timezone' => !empty($input['local_timezone']) ? $input['local_timezone'] : null,
        ];
        return $data;
    }

    public static function makeFreelancerSessionArray($input) {
        $data = array(
            'session_uuid' => UuidHelper::generateUniqueUUID(),
            'freelancer_uuid' => !empty($input['freelancer_uuid']) ? $input['freelancer_uuid'] : null,
            'customer_uuid' => !empty($input['customer_uuid']) ? $input['customer_uuid'] : null,
            'customer_name' => !empty($input['customer_name']) ? $input['customer_name'] : null,
            'session_date' => !empty($input['date']) ? $input['date'] : null,
            'from_time' => !empty($input['start_time']) ? $input['start_time'] : null,
            'to_time' => !empty($input['end_time']) ? $input['end_time'] : null,
            'address' => !empty($input['address']) ? $input['address'] : null,
            'lat' => !empty($input['lat']) ? $input['lat'] : null,
            'lng' => !empty($input['lng']) ? $input['lng'] : null,
            'price' => !empty($input['price']) ? $input['price'] : 0.00,
            'title' => !empty($input['title']) ? $input['title'] : null,
            'notes' => !empty($input['notes']) ? $input['notes'] : null,
            'service_uuid' => !empty($input['service_uuid']) ? $input['service_uuid'] : null,
        );
        return $data;
    }

    public static function makeFreelancerSessionServicesArray($inputs, $session_uuid) {
        $data = [];
//        foreach ($inputs['service_applied'] as $key => $row) {
//            $data[$key] = array(
//                'session_service_uuid' => UuidHelper::generateUniqueUUID(),
//                'session_uuid' => $session_uuid,
//                'service_uuid' => !empty($row) ? $row : null,
//            );
//        }
        $data[0]['session_service_uuid'] = UuidHelper::generateUniqueUUID();
        $data[0]['service_uuid'] = $inputs['service_uuid'];
        $data[0]['session_uuid'] = $session_uuid;
        return $data;
    }

    public static function makeFreelancerSessionDetailArray($data) {
        $resp = array(
            'session_uuid' => $data['session_uuid'],
            'date' => $data['session_date'],
            'start_time' => $data['from_time'],
            'end_time' => $data['to_time'],
            'location' => $data['location'],
            'price' => $data['price'],
            'status' => $data['status']
        );
        return $resp;
    }

}

?>
