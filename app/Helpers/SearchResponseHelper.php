<?php

namespace App\Helpers;

use App\Customer;
use Illuminate\Support\Facades\Validator;
use App\Client;
use App\WalkinCustomer;

Class SearchResponseHelper {
    /*
      |--------------------------------------------------------------------------
      | SearchResponseHelper that contains search related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use search processes
      |
     */

    /**
     * Description of SearchHelper
     *
     * @author ILSA Interactive
     */
    public static function searchedCustomersResponse($data = [], $related_data = []) {
        $response = [];
        if (!empty($data)) {
            foreach ($data as $key => $customer) {
                $response[$key]['customer_uuid'] = $customer['customer_uuid'];
                $response[$key]['first_name'] = $customer['first_name'];
                $response[$key]['last_name'] = $customer['last_name'];
                $response[$key]['public_chat'] = ($customer['public_chat'] == 1) ? true : false;
                $response[$key]['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse($customer['profile_image']);
                $response[$key]['has_subscribed'] = (in_array($customer['id'], $related_data['subscribers'])) ? true : false;
                $response[$key]['has_appointment'] = ((in_array($customer['id'], $related_data['appointment_customer_ids'])) ? true : false) || ((in_array($customer['id'], $related_data['customer_class_ids'])) ? true : false);
                $response[$key]['has_followed'] = (in_array($customer['id'], $related_data['followers'])) ? true : false;

//                        $receiver['has_appointment'] = ((in_array($receiver_data['customer_uuid'], $related_data['appointment_freelancer_ids'])) ? true : false) || ((in_array($receiver_data['customer_uuid'], $related_data['appointment_freelancer_ids'])) ? true : false);
            }
        }
        return $response;
    }

    public static function searchedFreelancersResponse($data_array = [], $related_data = []) {
        $response = [];
        if (!empty($data_array)) {
            foreach ($data_array as $key => $data) {
                $response[$key]['freelancer_uuid'] = $data['freelancer_uuid'];
                $response[$key]['first_name'] = !empty($data['first_name']) ? $data['first_name'] : null;
                $response[$key]['last_name'] = !empty($data['last_name']) ? $data['last_name'] : null;
                $response[$key]['public_chat'] = ($data['public_chat'] == 1) ? true : false;
                $response[$key]['profile_images'] = FreelancerResponseHelper::freelancerProfileImagesResponse($data['profile_image']);
                $response[$key]['has_subscribed'] = (in_array($data['id'], $related_data['subscribed_ids'])) ? true : false;
                $response[$key]['has_appointment'] = ((in_array($data['id'], $related_data['appointment_freelancer_ids'])) ? true : false) || ((in_array($data['id'], $related_data['class_freelancer_ids'])) ? true : false);
                $response[$key]['has_followed'] = (in_array($data['id'], $related_data['followings'])) ? true : false;
                ;
            }
        }
        return $response;
    }

}

?>
