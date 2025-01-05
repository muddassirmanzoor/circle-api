<?php

namespace App\Helpers;

Class ClientResponseHelper {
    /*
      |--------------------------------------------------------------------------
      | ClientResponseHelper that contains all the client methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use client processes
      |
     */

    public static function prepareClientResponse($data = []) {

        $response = [];
        $response['client_uuid'] = $data['client_uuid'];
        $response['customer_uuid'] = CommonHelper::getRecordByUuid('customers','id',$data['customer_id'],'customer_uuid');
        $response['freelancer_uuid'] = CommonHelper::getRecordByUuid('freelancers','id',$data['freelancer_id'],'freelancer_uuid');
        return $response;
    }

    public static function prepareClientDetailsResponse($data = [], $chat_data = []) {
        $response = [];
        if (!empty($data)) {
            $response['customer_uuid'] = !empty($data['customer_uuid']) ? $data['customer_uuid'] : $data['walkin_customer_uuid'];
            $response['type'] = !empty($data['customer_uuid']) ? "customer" : "walkin_customer";
            $response['first_name'] = $data['first_name'];
            $response['last_name'] = $data['last_name'];
            $response['public_chat'] = isset($data['public_chat']) ? (($data['public_chat'] == 1) ? true : false) : false;
            $response['has_subscription'] = !empty($chat_data['has_subscription']) ? $chat_data['has_subscription'] : false;
            $response['has_appointment'] = !empty($chat_data['has_appointment']) ? $chat_data['has_appointment'] : false;
            $response['email'] = !empty($data['email']) ? $data['email'] : null;
            $response['phone_number'] = !empty($data['phone_number']) ? $data['phone_number'] : null;
//            $response['profile_image'] = !empty($data['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_image'] . $data['profile_image'] : null;
            $response['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse(!empty($data['profile_image']) ? $data['profile_image'] : null);
            $response['cover_images'] = CustomerResponseHelper::customerCoverImagesResponse(!empty($data['cover_image']) ? $data['cover_image'] : null);
            $response['appointments_count'] = $data['appointments_count'];
            $response['appointments_revenue'] = $data['appointments_revenue'];
            $response['history_count'] = !empty($data['history_count']) ? $data['history_count'] : 0;
            $response['industry_image'] = isset($data['interests'][0]['category']['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['category_image'] . $data['interests'][0]['category']['image'] : null;
        }
        return $response;
    }

    public static function prepareMixedClientsResponse($customers = [], $clients = []) {
        if (!empty($customers) && !empty($clients)) {
            foreach ($customers as $key => $customer) {
                foreach ($clients as $client) {

                    //if ($client['customer_uuid'] == $customer['customer_uuid']) {
                    if ($client['customer_id'] == $customer['id']) {
                        $customers[$key]['client_uuid'] = $client['client_uuid'];
                        break;
                    }
                }
            }
        }
        return $customers;
    }



    public static function addClientUuidinResponse($data = [], $clients = [], $freelancer_uuid = null) {
        if (!empty($data)) {
            foreach ($data as $key => $customer) {

                foreach ($clients as $index => $client) {
                    if ($customer['id'] == $client['id'] && $client['freelancer_id'] == $freelancer_uuid) {
                        $data[$key]['client_uuid'] = $client['client_uuid'];
                    }
                }
            }
        }
        return $data;
    }

    public static function clientListResponse($data = []) {
        $response = [];
        if (!empty($data)) {
            foreach ($data as $key => $customer) {
                $response[$key]['client_uuid'] = $customer['client_uuid'];
                if (!empty($customer['customer'])) {
                    $response[$key]['customer_uuid'] = $customer['customer']['customer_uuid'];
                    $response[$key]['first_name'] = $customer['customer']['first_name'];
                    $response[$key]['last_name'] = $customer['customer']['last_name'];
                    $response[$key]['email'] = $customer['customer']['email'];
                    $response[$key]['phone_number'] = $customer['customer']['phone_number'];
                    $response[$key]['gender'] = $customer['customer']['gender'];
                    $response[$key]['appointments_count'] = !empty($customer['customer']['appointment_client']) ? count($customer['customer']['appointment_client']) : 0;
//                    $response[$key]['profile_image'] = !empty($customer['customer']['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_image'] . $customer['customer']['profile_image'] : null;
                    $response[$key]['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse($customer['customer']['profile_image']);
                } elseif (!empty($customer['walkin_customer'])) {
                    $response[$key]['customer_uuid'] = $customer['walkin_customer']['walkin_customer_uuid'];
                    $response[$key]['first_name'] = $customer['walkin_customer']['first_name'];
                    $response[$key]['last_name'] = !empty($customer['walkin_customer']['last_name']) ? $customer['walkin_customer']['last_name'] : null;
                    $response[$key]['email'] = null;
                    $response[$key]['phone_number'] = null;
                    $response[$key]['gender'] = null;
                    $response[$key]['appointments_count'] = !empty($customer['walkin_customer']['appointment_client']) ? count($customer['walkin_customer']['appointment_client']) : 0;
//                    $response[$key]['profile_image'] = !empty($customer['walkin_customer']['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_image'] . $customer['walkin_customer']['profile_image'] : null;
                    $response[$key]['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse(!empty($customer['walkin_customer']['profile_image']) ? $customer['walkin_customer']['profile_image'] : null);
                }
            }
        }
        return $response;
    }

}

?>
