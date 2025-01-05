<?php

namespace App\Helpers;

Class WalkinCustomerResponseHelper {
    /*
      |--------------------------------------------------------------------------
      | WalkinCustomerResponseHelper that contains all the customer methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use customer processes
      |
     */

    public static function WalkinCustomerResponse($customer = []) {
        $response = [];
        if (!empty($customer)) {
            $response['customer_uuid'] = $customer['customer_uuid'];
            $response['type'] = $customer['type'];
            $response['first_name'] = $customer['first_name'];
            $response['last_name'] = !empty($customer['last_name']) ? $customer['last_name'] : null;
            $response['email'] = null;
            $response['phone_number'] = null;
            $response['gender'] = null;
//            $response['profile_image'] = !empty($customer['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_image'] . $customer['profile_image'] : null;
            //$response['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse(!empty($customer['profile_image']) ? $customer['profile_image'] : null);
        }
        return $response;
    }

    public static function searchWalkinCustomersResponse($customers = []) {
        $response = [];
        if (!empty($customers)) {
            foreach ($customers as $key => $customer) {
                $response[$key]['customer_uuid'] = $customer['walkin_customer_uuid'];
                $response[$key]['type'] = "walkin_customer";
                $response[$key]['first_name'] = $customer['first_name'];
                $response[$key]['last_name'] = !empty($customer['last_name']) ? $customer['last_name'] : null;
                $response[$key]['email'] = null;
                $response[$key]['phone_number'] = null;
                $response[$key]['gender'] = null;
                $response[$key]['appointments_count'] = !empty($customer['appointment_client']) ? count($customer['appointment_client']) : 0;
//                $response[$key]['profile_image'] = !empty($customer['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_image'] . $customer['profile_image'] : null;
                $response[$key]['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse(!empty($customer['profile_image']) ? $customer['profile_image'] : null);
            }
        }
        return $response;
    }

}

?>
