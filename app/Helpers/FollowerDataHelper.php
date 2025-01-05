<?php

namespace App\Helpers;
use App\Subscription;
Class FollowerDataHelper {
    /*
      |--------------------------------------------------------------------------
      | ReviewDataHelper that contains all the Freelancer data methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use Freelancer processes
      |
     */

    public static function makeFreelancerFollowerResponse($data = []) {
//        $response = [];
//
//        $response[0]['follower_uuid'] = "469cd89a-c8e7-4a9d-b1c7-9bd935489fec";
//        $response[0]['following_uuid'] = "4392d319-a051-4b92-a80c-1b8u7y8u7y9p";
//        $response[0]['first_name'] = "John";
//        $response[0]['last_name'] = "Doe";
//        $response[0]['profile_image'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/profile_images/customers/5e68c3dad88c11583924186.jpg";
//        $response[0]['date'] = "2020-03-20";
//        $response[0]['is_subscriber'] = false;
//
//        $response[1]['follower_uuid'] = "469cd89a-c8e7-4a9d-b1c7-9bd935489fec";
//        $response[1]['following_uuid'] = "4392d319-a051-4b92-a80c-1b8u7y8u7y9p";
//        $response[1]['first_name'] = "John";
//        $response[1]['last_name'] = "Doe";
//        $response[1]['profile_image'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/profile_images/customers/5e68c3dad88c11583924186.jpg";
//        $response[1]['date'] = "2020-03-19";
//        $response[1]['is_subscriber'] = true;
//
//        $response[2]['follower_uuid'] = "469cd89a-c8e7-4a9d-b1c7-9bd935489fec";
//        $response[2]['following_uuid'] = "4392d319-a051-4b92-a80c-1b8u7y8u7y9p";
//        $response[2]['first_name'] = "John";
//        $response[2]['last_name'] = "Doe";
//        $response[2]['profile_image'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/profile_images/customers/5e68c3dad88c11583924186.jpg";
//        $response[2]['date'] = "2020-03-17";
//        $response[2]['is_subscriber'] = false;
//
//        $response[3]['follower_uuid'] = "469cd89a-c8e7-4a9d-b1c7-9bd935489fec";
//        $response[3]['following_uuid'] = "4392d319-a051-4b92-a80c-1b8u7y8u7y9p";
//        $response[3]['first_name'] = "John";
//        $response[3]['last_name'] = "Doe";
//        $response[3]['profile_image'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/profile_images/customers/5e68c3dad88c11583924186.jpg";
//        $response[3]['date'] = "2020-03-17";
//        $response[3]['is_subscriber'] = false;

        $response = [];
        if (!empty($data)) {
            foreach ($data as $key => $row) {
                $response[$key]['follower_uuid'] = CommonHelper::getRecordByUuid('customers','id', $row['follower_id'],'customer_uuid');
                $response[$key]['following_uuid'] = CommonHelper::getRecordByUuid('freelancers','id',$row['following_id'],'freelancer_uuid');
                $response[$key]['first_name'] = !empty($row['customer']['first_name']) ? $row['customer']['first_name'] : null;
                $response[$key]['last_name'] = !empty($row['customer']['last_name']) ? $row['customer']['last_name'] : null;
                $response[$key]['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse($row['customer']['profile_image']);
                $response[$key]['date'] = date("Y-m-d", strtotime($row['created_at']));
                $response[$key]['is_subscriber'] = Subscription::checkSubscriber($row['follower_id'], $row['following_id']);
            }
        }
        return $response;
    }

    public static function followersResponse($followings = []) {
        $response = [];
        if (!empty($followings)) {
            $key = 0;
            foreach ($followings as $data) {
                if (!empty($data['freelancer'])) {
                    $response[$key]['freelancer_uuid'] = $data['freelancer']['freelancer_uuid'];
                    $response[$key]['first_name'] = !empty($data['freelancer']['first_name']) ? $data['freelancer']['first_name'] : null;
                    $response[$key]['last_name'] = !empty($data['freelancer']['last_name']) ? $data['freelancer']['last_name'] : null;
//                    $response[$key]['profile_image'] = !empty($data['freelancer']['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $data['freelancer']['profile_image'] : null;
                    $response[$key]['profile_images'] = FreelancerResponseHelper::freelancerProfileImagesResponse($data['freelancer']['profile_image']);
                    $response[$key]['has_story'] = true;
                    $response[$key]['stories'] = StoryResponseHelper::processStoriesResponse($data['freelancer']['active_stories']);
                    $response[$key]['profession_details'] = LoginHelper::processFreelancerProfessionResponse(!empty($data['freelancer']['profession']) ? $data['freelancer']['profession'] : []);

                    $key++;
                }
            }
        }
        return $response;
    }

    public static function suggestionsResponse($profiles = []) {
        $response = [];
        if (!empty($profiles)) {
            $key = 0;
            foreach ($profiles as $data) {
                if (!empty($data)) {
                    $response[$key]['freelancer_uuid'] = $data['freelancer']['freelancer_uuid'];
                    $response[$key]['first_name'] = !empty($data['freelancer']['first_name']) ? $data['freelancer']['first_name'] : null;
                    $response[$key]['last_name'] = !empty($data['freelancer']['last_name']) ? $data['freelancer']['last_name'] : null;
//                    $response[$key]['profile_image'] = !empty($data['freelancer']['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $data['freelancer']['profile_image'] : null;
                    $response[$key]['profile_images'] = FreelancerResponseHelper::freelancerProfileImagesResponse($data['freelancer']['profile_image']);
                    $response[$key]['profession_details'] = LoginHelper::processFreelancerProfessionResponse(!empty($data['freelancer']['profession']) ? $data['freelancer']['profession'] : []);
                    $key++;
                }
            }
        }

        return $response;
    }

}

?>
