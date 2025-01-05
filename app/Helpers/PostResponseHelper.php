<?php

namespace App\Helpers;

use App\Customer;
use App\Follower;

Class PostResponseHelper {
    /*
      |--------------------------------------------------------------------------
      | PostResponseHelper that contains all the post response methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use post processes
      |
     */

    public static function preparePostResponse($data = [], $liked_by_users_ids = [], $logged_in_uuid = null) {
        $response = [];
        if (!empty($data)) {
            foreach ($data as $key => $value) {
//        for ($key = 0; $key <= 10; $key++) {
                $response[$key]['post_uuid'] = $value['post_uuid'];
                $response[$key]['profile_uuid'] = $value['profile_uuid'];
                $response[$key]['title'] = !empty($value['caption']) ? $value['caption'] : null;
                $response[$key]['content'] = !empty($value['text']) ? $value['text'] : null;
                $response[$key]['post_type'] = $value['post_type'];
                $response[$key]['media_type'] = $value['media_type'];
//              $response[$key]['thumbnail'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/general/5e985179c40311587040633.jpg";
                $response[$key]['thumbnail'] = !empty($value['media']['video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['post_video_thumb'] . $value['media']['media_src'] : null;
                $response[$key]['video'] = !empty($value['media']['media_src']) && $value['media_type'] == 'video' ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['post_video'] . $value['media']['media_src'] : null;
                $response[$key]['image'] = !empty($value['media']['media_src']) && $value['media_type'] == 'image' ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['post_image'] . $value['media']['media_src'] : null;
                $response[$key]['likes_count'] = 0;
                $response[$key]['is_liked'] = (in_array($logged_in_uuid, $liked_by_users_ids)) ? true : false;

                $response[$key]['created_at'] = $value['created_at'];
                if (!empty($value['freelancer'])) {
                    $response[$key]['profile'] = self::prepareFreelancerResponse($value['freelancer']);
                }
//                $response[$key]['user'] = self::prepareUserResponse();
//            $response[$key]['images'] = self::prepareImageResponse();
//            if (!empty($data['videos'])) {
//                $response[$key]['videos'] = self::prepareVideoResponse();
            }
//        }
        }
        return $response;
    }

    public static function prepareCustomerFeedPostResponse($post = [], $logged_in_uuid = null, $data_to_validate = []) {
        $response = [];
        $customerId = Customer::where('user_id', $logged_in_uuid)->first()?->id;
        if (!empty($post)) {
//            $url = $_SERVER['HTTP_HOST'];
            $response['post_uuid'] = $post['post_uuid'];
            $response['profile_uuid'] = CommonHelper::getRecordByUuid('freelancers', 'id', $post['freelance_id'], 'freelancer_uuid');
            $response['title'] = !empty($post['caption']) ? $post['caption'] : null;
            $response['content'] = !empty($post['text']) ? $post['text'] : null;
            $response['folder_uuid'] = !empty($post['folder_uuid']) ? $post['folder_uuid'] : null;
            $response['is_intro'] = !empty($post['is_intro']) ? $post['is_intro'] : null;
            $response['post_type'] = $post['post_type'];
            $response['media_type'] = $post['media_type'];
            $response['post_action_type'] = $post['post_action_type'];
//            $response['thumbnail'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/general/5e985179c40311587040633.jpg";


            $response['thumbnail'] = !empty($post['media']['video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['post_video_thumb'] . $post['media']['video_thumbnail'] : null;
            $response['video'] = (!empty($post['media']['media_src']) && $post['media_type'] == 'video') ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['post_video'] . $post['media']['media_src'] : null;
            $response['image'] = (!empty($post['media']['media_src']) && $post['media_type'] == 'image') ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['post_image'] . $post['media']['media_src'] : null;
            $response['height'] = $post['media']['height'] ?? '';
            $response['width'] = $post['media']['width'] ?? '';

//            $response['thumbnail'] = !empty($post['media']['video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['post_video_thumb'] . $post['media']['video_thumbnail'] : null;
//            $response['video'] = !empty($post['media']['media_src']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['post_video'] . $post['video']['media_src'] : null;
//            $response['image'] = !empty($post['image']['post_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['post_image'] . $post['image']['post_image'] : null;
//            $response['height'] = !empty($post['video']['height']) ? $post['video']['height'] : $post['image']['height'];
//            $response['width'] = !empty($post['video']['width']) ? $post['video']['width'] : $post['image']['width'];


            $response['likes_count'] = !empty($data_to_validate['likes_count']) ? $data_to_validate['likes_count'] : 0;
            //$logged_in_uuid
            //is the pk of user table
            $response['is_liked'] = (!empty($data_to_validate['liked_by_users_ids']) && in_array($logged_in_uuid, $data_to_validate['liked_by_users_ids'])) ? true : false;
            $response['is_bookmarked'] = (!empty($data_to_validate['bookmarked_ids']) && in_array($post['id'], $data_to_validate['bookmarked_ids'])) ? true : false;
            $response['is_following'] = Follower::checkFollowing($customerId, $post['freelance_id']);
            $response['created_at'] = $post['created_at'];
            $response['folder_name'] = !empty($post['folders']) ? $post['folders']['name'] : null;
            $response['part_no'] = $post['part_no'];
            $response['share_url'] = self::preparePostShareURL($post);

//            $data_string = "post_uuid=" . $post['post_uuid'];
//            $encoded_string = base64_encode($data_string);
//            if (strpos($url, 'localhost') !== false) {
//                $response['share_url'] = "http://localhost/wellhello-php-api/getPostDetail" . "?" . $encoded_string;
//            } elseif (strpos($url, 'staging') !== false) {
//                $response['share_url'] = config("general.url.staging_url") . "getPostDetail?" . $encoded_string;
//            } elseif (strpos($url, 'dev') !== false) {
//                $response['share_url'] = config("general.url.development_url") . "getPostDetail?" . $encoded_string;
//            } elseif (strpos($url, 'production') !== false) {
//                $response['share_url'] = config("general.url.production_url") . "getPostDetail?" . $encoded_string;
//            }
            if (!empty($post['freelancer'])) {
                $response['profile'] = self::prepareFreelancerResponse($post['freelancer']);
            }
        }
        return $response;
    }

    public static function prepareImageResponse($data = []) {
        $response = [];
//        if (!empty($data)) {
        $response[0]['image_uuid'] = "cc03e187-be61-4ed9-8f8a-2e6d28c55fa0";
        $response[0]['image'] = "http://d1yfi7d5wse973.cloudfront.net/uploads/items/f9tcdilvbeauty1552565432.jpg";
        $response[1]['image_uuid'] = "cc03e187-be61-4ed9-8f8a-2e6d28c55fa1";
        $response[1]['image'] = "http://d1yfi7d5wse973.cloudfront.net/uploads/items/zavprltkpexels-photo-16264811552565610.jpg";
        $response[2]['image_uuid'] = "cc03e187-be61-4ed9-8f8a-2e6d28c55fa2";
        $response[2]['image'] = "http://d1yfi7d5wse973.cloudfront.net/uploads/items/xrr7xgqihumor1552566389.jpg";

//        }
        return $response;
    }

    public static function prepareVideoResponse($data = []) {
        $response = [];
//        if (!empty($data)) {
        $response[0]['video_uuid'] = "ac03e187-be61-4ed9-8f8a-2e6d28c55fa0";
        $response[0]['video'] = null;
        $response[0]['thumbnail'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/general/5e985179c40311587040633.jpg";
        $response[1]['video_uuid'] = "ac03e187-be61-4ed9-8f8a-2e6d28c55fa1";
        $response[1]['video'] = null;
        $response[1]['thumbnail'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/general/5e985179c40311587040633.jpg";
        $response[2]['video_uuid'] = "ac03e187-be61-4ed9-8f8a-2e6d28c55fa2";
        $response[2]['video'] = null;
        $response[2]['thumbnail'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/general/5e985179c40311587040633.jpg";

//        }
        return $response;
    }

    public static function prepareUserResponse($data = []) {
        $response = [];
//        if (!empty($data)) {
        $response['freelancer_uuid'] = "dc03e187-be61-4ed9-8f8a-2e6d28c55fa0";
        $response['first_name'] = "Salman";
        $response['last_name'] = "Khan";
        $response['profession'] = "Engineering";
//        $response['profile_image'] = "http://d2bp2kgc0vgu09.cloudfront.net/uploads/profile_images/customers/5e68c3dad88c11583924186.jpg";
        $response['profile_images'] = FreelancerResponseHelper::freelancerProfileImagesResponse();
//        }
        return $response;
    }

    public static function prepareProfilePostResponse($data = [], $data_to_validate = []) {
        $response = [];
//        return $data;
        if (!empty($data)) {
            foreach ($data as $key => $value) {

                $response[$key]['post_uuid'] = $value['post_uuid'];
                $response[$key]['profile_uuid'] = CommonHelper::getRecordByUuid('freelancers', 'id', $value['freelance_id'], 'freelancer_uuid');
                $response[$key]['folder_uuid'] = CommonHelper::getRecordByUuid('folders', 'id', $value['folder_id'], 'folder_uuid');
                $response[$key]['title'] = !empty($value['caption']) ? $value['caption'] : null;
                $response[$key]['content'] = !empty($value['text']) ? $value['text'] : null;
                $response[$key]['post_type'] = $value['post_type'];
                $response[$key]['media_type'] = $value['media_type'];
                $response[$key]['part_no'] = $value['part_no'];
                $response[$key]['is_intro'] = $value['is_intro'];
                $response[$key]['thumbnail'] = !empty($value['media']['video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['post_video_thumb'] . $value['media']['video_thumbnail'] : null;
                $response[$key]['video'] = (!empty($value['media']['media_src']) && $value['media_type'] == 'video') ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['post_video'] . $value['media']['media_src'] : null;
                $response[$key]['image'] = (!empty($value['media']['media_src']) && $value['media_type'] == 'image') ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['post_image'] . $value['media']['media_src'] : null;
//                $response[$key]['duration'] = !empty($value['media']['post_video']) ? $value['video']['duration'] : 0;
                $response[$key]['duration'] = $value['media_type'] == 'video' && !empty($value['media']['duration']) ? $value['media']['duration'] : 0;
                $response[$key]['is_bookmarked'] = (!empty($data_to_validate['bookmarked_ids']) && in_array($value['id'], $data_to_validate['bookmarked_ids'])) ? true : false;
//                $response[$key]['likes_count'] = 23;
                $response[$key]['share_url'] = self::preparePostShareURL($value);
                $response[$key]['created_at'] = $value['created_at'];
                if (!empty($value['freelancer'])) {
                    $response[$key]['profile'] = self::prepareFreelancerResponse($value['freelancer']);
                }
                if (!empty($value['customer'])) {
                    $response[$key]['profile'] = self::prepareCustomerResponse($value['customer']);
                }
            }
        }
        return $response;
    }

    public static function prepareFreelancerResponse($data = []) {
        $response = [];
        if (!empty($data)) {
            $response['freelancer_uuid'] = $data['freelancer_uuid'];
            $response['first_name'] = !empty($data['first_name']) ? $data['first_name'] : null;
            $response['last_name'] = !empty($data['last_name']) ? $data['last_name'] : null;
            $response['profession_details'] = LoginHelper::processFreelancerProfessionResponse(!empty($data['profession']) ? $data['profession'] : []);
            $response['is_verified'] = true;
            $response['rating_count'] = 23;
            $response['average_rating'] = 4.5;
            $response['profile_images'] = FreelancerResponseHelper::freelancerProfileImagesResponse($data['profile_image']);
//            $response['profile_image'] = !empty($data['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $data['profile_image'] : null;
        }
        return $response;
    }

    public static function prepareCustomerResponse($data = []) {
        $response = [];
        if (!empty($data)) {
            $response['customer_uuid'] = $data['customer_uuid'];
            $response['first_name'] = !empty($data['first_name']) ? $data['first_name'] : null;
            $response['last_name'] = !empty($data['last_name']) ? $data['last_name'] : null;
            $response['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse($data['profile_image']);
//            $response['profile_image'] = !empty($data['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_image'] . $data['profile_image'] : null;
        }
        return $response;
    }

    public static function preparePostDetailResponse($value = [], $logged_in_uuid = null, $data_to_validate = []) {
        $response = [];
        if (!empty($value)) {
//            $url = $_SERVER['HTTP_HOST'];
            $post_id = CommonHelper::getRecordByUuid('posts', 'post_uuid', $value['post_uuid']);
            $response['post_uuid'] = $value['post_uuid'];
            $response['profile_uuid'] = CommonHelper::getRecordByUuid('freelancers', 'id', $value['freelance_id'], 'freelancer_uuid');
            $response['title'] = !empty($value['caption']) ? $value['caption'] : null;
            $response['content'] = !empty($value['text']) ? $value['text'] : null;
            $response['post_type'] = $value['post_type'];
            $response['media_type'] = $value['media_type'];
            $response['part_no'] = $value['part_no'];
            $response['thumbnail'] = !empty($value['media']['video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['post_video_thumb'] . $value['media']['video_thumbnail'] : null;
            $response['video'] = !empty($value['media']['media_src']) && $value['media']['media_type'] == 'video' ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['post_video'] . $value['media']['media_src'] : null;
            $response['image'] = !empty($value['media']['media_src']) && $value['media']['media_type'] == 'image' ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['post_image'] . $value['media']['media_src'] : null;
            $response['duration'] = $value['media']['post_video'] ?? 0;
            $response['likes_count'] = !empty($value['likes']) ? count($value['likes']) : 0;
            $response['is_liked'] = (in_array($logged_in_uuid, $data_to_validate['liked_by_users_ids'])) ? true : false;
            $response['is_bookmarked'] = (!empty($data_to_validate['bookmarked_ids']) && in_array($post_id, $data_to_validate['bookmarked_ids'])) ? true : false;
            $response['created_at'] = $value['created_at'];
            $response['address'] = self::prepareLocationResponse($value);
            $response['share_url'] = self::preparePostShareURL($value);

//            $data_string = "post_uuid=" . $value['post_uuid'];
//            $encoded_string = base64_encode($data_string);
//            if (strpos($url, 'localhost') !== false) {
//                $response['share_url'] = "http://localhost/wellhello-php-api/getPostDetail" . "?" . $encoded_string;
//            } elseif (strpos($url, 'staging') !== false) {
//                $response['share_url'] = config("general.url.staging_url") . "getPostDetail?" . $encoded_string;
//            } elseif (strpos($url, 'dev') !== false) {
//                $response['share_url'] = config("general.url.development_url") . "getPostDetail?" . $encoded_string;
//            } elseif (strpos($url, 'production') !== false) {
//                $response['share_url'] = config("general.url.production_url") . "getPostDetail?" . $encoded_string;
//            }
            if (!empty($value['freelancer'])) {
                $response['profile'] = self::prepareFreelancerResponse($value['freelancer']);
            }
            if (!empty($value['customer'])) {
                $response['profile'] = self::prepareCustomerResponse($value['customer']);
            }
        }
        return $response;
    }

    public static function prepareLocationResponse($post) {
        $response = [];
        if (!empty($post['locations'])) {
            foreach ($post['locations'] as $key => $location) {
                if (!empty($location)) {
                    $response['address'] = !empty($location['address']) ? $location['address'] : null;
                    $response['lat'] = !empty($location['lat']) ? $location['lat'] : null;
                    $response['lng'] = !empty($location['lng']) ? $location['lng'] : null;
                }
            }
        }
        return $response ? $response : null;
    }

    public static function preparePostShareURL($inputs) {
        $share_url = "";
        if (!empty($inputs)) {
            $url = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "production";
            $data_string = "post_uuid=" . $inputs['post_uuid'];
            $encoded_string = base64_encode($data_string);
            if (strpos($url, 'localhost') !== false) {
                $share_url = "http://localhost/wellhello-php-api/getPostDetail" . "?" . $encoded_string;
            } elseif (strpos($url, 'staging') !== false) {
                $share_url = config("general.url.staging_url") . "getPostDetail?" . $encoded_string;
            } elseif (strpos($url, 'dev') !== false) {
                $share_url = config("general.url.development_url") . "getPostDetail?" . $encoded_string;
            } elseif (strpos($url, 'production') !== false) {
                $share_url = config("general.url.production_url") . "getPostDetail?" . $encoded_string;
            }
        }
        return $share_url;
    }

    public static function getMultiPostResponse($data) {
        $response = [];
        if (isset($data['posts']) && !empty($data['posts'])) {
            foreach ($data['posts'] as $key => $post) {
                $response[$key] = self::prepareCustomerFeedPostResponse(!empty($post) ? $post : []);
            }
        }
        return $response;
    }

}

?>
