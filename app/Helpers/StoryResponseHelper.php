<?php

namespace App\Helpers;

use App\Freelancer;

Class StoryResponseHelper {
    /*
      |--------------------------------------------------------------------------
      | StoryResponseHelper that contains all the story response methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use story processes
      |
     */

    public static function processStoriesResponse($data = [], $data_to_validate = [],$login_user_type=null) {
        $response = [];
        if (!empty($data)) {

            foreach ($data as $key => $value) {


                $response[$key]['story_uuid'] = array_key_exists('story_uuid',$value) ? $value['story_uuid'] : CommonHelper::getRecordByUuid('stories','id', $value['story_id'],'story_uuid');
//                    $response[$key]['story_uuid'] = CommonHelper::getRecordByUuid('stories','id', $value['story_uuid'],'story_uuid');


                $response[$key]['profile_uuid'] = CommonHelper::getRecordByUuid('freelancers','id',$value['freelancer_id'],'freelancer_uuid');

                $response[$key]['text'] = !empty($value['text']) ? $value['text'] : null;
                $response[$key]['url'] = !empty($value['url']) ? $value['url'] : null;
                $response[$key]['story_image'] = !empty($value['story_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['image_stories'] . $value['story_image'] : null;
                $response[$key]['story_video'] = !empty($value['story_video']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['video_stories'] . $value['story_video'] : null;
                $response[$key]['time_ago'] = $value['created_at'];
                $response[$key]['is_seen'] = (!empty($data_to_validate['story_uuid_array']) && in_array($value['id'], $data_to_validate['story_uuid_array'])) ? true : false;
                $response[$key]['address'] = !empty($value['story_location']) ? $value['story_location']['address'] : null;
                $response[$key]['lat'] = !empty($value['story_location']['lat']) ? $value['story_location']['lat'] : null;
                $response[$key]['lng'] = !empty($value['story_location']['lng']) ? $value['story_location']['lng'] : null;
                $response[$key]['video_thumbnail'] = null;
                if (!empty($value['video_thumbnail'])) {
                    $response['video_thumbnail'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/general/5e985179c40311587040633.jpg";
                    $response[$key]['video_thumbnail'] = !empty($value['video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['video_story_thumb'] . $value['video_thumbnail'] : null;
                }
//                $response[$key]['profile'] = self::preprareProfileResponse($value['freelancer']);
            }
        }
        return $response;
    }

    public static function processSingleStoryResponse($data = [], $data_to_validate = []) {
        $response = [];

        if (!empty($data)) {
            $response['story_uuid'] =$data['story_uuid'];
            $response['profile_uuid'] = CommonHelper::getRecordByUuid('freelancers','id',$data['freelancer_id'],'freelancer_uuid');
            $response['text'] = !empty($data['text']) ? $data['text'] : null;
            $response['url'] = !empty($data['url']) ? $data['url'] : null;
            $response['story_image'] = !empty($data['story_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['image_stories'] . $data['story_image'] : null;
            $response['story_video'] = !empty($data['story_video']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['video_stories'] . $data['story_video'] : null;
            $response['time_ago'] = $data['created_at'];
            $response['is_seen'] = (!empty($data_to_validate['story_uuid_array']) && in_array($data['story_uuid'], $data_to_validate['story_uuid_array'])) ? true : false;
            $response['address'] = !empty($data['story_location']) ? $data['story_location']['address'] : null;
            $response['video_thumbnail'] = null;
            if (!empty($data['video_thumbnail'])) {
                $response['video_thumbnail'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/general/5e985179c40311587040633.jpg";
                $response['video_thumbnail'] = !empty($data['video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['video_story_thumb'] . $data['video_thumbnail'] : null;
            }
        }
        return $response;
    }

    public static function preprareProfileResponse($data = []) {
        $response = [];
        if (!empty($data)) {
            $response['profile_uuid'] = $data['freelancer_uuid'];
            $response['first_name'] = !empty($data['first_name']) ? $data['first_name'] : null;
            $response['last_name'] = !empty($data['last_name']) ? $data['last_name'] : null;
//            $response['profile_image'] = !empty($data['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $data['profile_image'] : null;
            $response['profile_images'] = FreelancerResponseHelper::freelancerProfileImagesResponse($data['profile_image']);
        }
        return $response;
    }

    public static function prepareFeedStoriesResponse($profiles = [], $data_to_validate = []) {
        $response = [];
        if (!empty($profiles)) {
            $key = 0;
            foreach ($profiles as $data) {
                if (!empty($data)) {
                    $response[$key]['freelancer_uuid'] = $data['freelancer_uuid'];
                    $response[$key]['first_name'] = !empty($data['first_name']) ? $data['first_name'] : null;
                    $response[$key]['last_name'] = !empty($data['last_name']) ? $data['last_name'] : null;
                    $response[$key]['profession_details'] = LoginHelper::processFreelancerProfessionResponse(!empty($data['profession']) ? $data['profession'] : []);
//                    $response[$key]['profile_image'] = !empty($data['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $data['profile_image'] : null;
                    $response[$key]['profile_images'] = FreelancerResponseHelper::freelancerProfileImagesResponse($data['profile_image']);
                    $response[$key]['has_story'] = true;
                    $response[$key]['timestamp'] = !empty($data['active_stories'][0]['created_at']) ? $data['active_stories'][0]['created_at'] : null;
                    $response[$key]['stories'] = StoryResponseHelper::processStoriesResponse($data['active_stories'], $data_to_validate);
                    $key++;
                }
            }
        }
        return $response;
    }

}

?>
