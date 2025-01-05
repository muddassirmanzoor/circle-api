<?php

namespace App\Helpers;

Class SubscriberResponseHelper {
    /*
      |--------------------------------------------------------------------------
      | SubscriberResponseHelper that contains all the subscriber methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use subscriber processes
      |
     */

    public static function makeSubscriberResponse($data = []) {
        $response = [];
//        $response[0]['subscription_uuid'] = "kkghsdkg-sjgfksj-895439857h-jsdkvkj8";
//        $response[0]['subscriber_uuid'] = "469cd89a-c8e7-4a9d-b1c7-9bd935489fec";
//        $response[0]['subscribed_uuid'] = "4392d319-a051-4b92-a80c-1b8u7y8u7y9p";
//        $response[0]['first_name'] = "Alex";
//        $response[0]['last_name'] = "Smith";
//        $response[0]['profile_image'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/profile_images/customers/5e68c3dad88c11583924186.jpg";
//        $response[0]['date'] = "2020-03-20";
//
//        $response[1]['subscription_uuid'] = "kkghsdkg-sjgfksj-895439857h-jsdkvkj8";
//        $response[1]['subscriber_uuid'] = "469cd89a-c8e7-4a9d-b1c7-9bd935489fec";
//        $response[1]['subscribed_uuid'] = "4392d319-a051-4b92-a80c-1b8u7y8u7y9p";
//        $response[1]['first_name'] = "Alex";
//        $response[1]['last_name'] = "Smith";
//        $response[1]['profile_image'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/profile_images/customers/5e68c3dad88c11583924186.jpg";
//        $response[1]['date'] = "2020-03-19";
//
//        $response[2]['subscription_uuid'] = "kkghsdkg-sjgfksj-895439857h-jsdkvkj8";
//        $response[2]['subscriber_uuid'] = "469cd89a-c8e7-4a9d-b1c7-9bd935489fec";
//        $response[2]['subscribed_uuid'] = "4392d319-a051-4b92-a80c-1b8u7y8u7y9p";
//        $response[2]['first_name'] = "Alex";
//        $response[2]['last_name'] = "Smith";
//        $response[2]['profile_image'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/profile_images/customers/5e68c3dad88c11583924186.jpg";
//        $response[2]['date'] = "2020-03-18";
//
//        $response[3]['subscription_uuid'] = "kkghsdkg-sjgfksj-895439857h-jsdkvkj8";
//        $response[3]['subscriber_uuid'] = "469cd89a-c8e7-4a9d-b1c7-9bd935489fec";
//        $response[3]['subscribed_uuid'] = "4392d319-a051-4b92-a80c-1b8u7y8u7y9p";
//        $response[3]['first_name'] = "Alex";
//        $response[3]['last_name'] = "Smith";
//        $response[3]['profile_image'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/profile_images/customers/5e68c3dad88c11583924186.jpg";
//        $response[3]['date'] = "2020-03-17";


        $response = [];
        if (!empty($data)) {
            foreach ($data as $key => $row) {
                $response[$key]['subscription_uuid'] = $row['subscription_uuid'];
                $response[$key]['subscriber_id'] = CommonHelper::getRecordByUuid('customers','id', $row['subscriber_id'],'customer_uuid');
                $response[$key]['subscribed_id'] = CommonHelper::getRecordByUuid('freelancers','id', $row['subscribed_id'],'freelancer_uuid');
                $response[$key]['first_name'] = !empty($row['customer']['first_name']) ? $row['customer']['first_name'] : null;
                $response[$key]['last_name'] = !empty($row['customer']['last_name']) ? $row['customer']['last_name'] : null;
//                $response[$key]['profile_image'] = !empty($row['customer']['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_image'] . $row['customer']['profile_image'] : null;
                $response[$key]['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse($row['customer']['profile_image']);
                $response[$key]['date'] = date('Y-m-d', strtotime($row['subscription_date']));
            }
        }
        return $response;
    }

}

?>
