<?php

namespace App\Helpers;

Class SubscriberDataHelper {
    /*
      |--------------------------------------------------------------------------
      | SubscriberDataHelper that contains all the Subscriber data methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use Subscriber processes
      |
     */

    public static function makeFreelancerSubscriberArray($data = []) {
        $response = [];
        if (!empty($data)) {
            foreach ($data as $key => $row) {
                $follower = $row['get_follower'];
                $response[$key]['customer_uuid'] = $row['follower_uuid'];
                $response[$key]['following_since'] = $row['created_at'];
                $response[$key]['customer_name'] = $follower['first_name'] . ' ' . $follower['last_name'];
                $response[$key]['customer_picture'] = !empty($follower['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_image'] . $follower['profile_image'] : null;
                $response[$key]['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse($follower['profile_image']);
            }
        }
        return $response;
    }

}

?>
