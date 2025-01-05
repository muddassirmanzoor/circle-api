<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Post;
use App\Folder;
use App\Subscription;
use App\PostImage;
use App\PostVideo;
use App\Location;
use App\ReportedPost;
use App\PostLocation;
use Ramsey\Uuid\Uuid;
use App\Like;
use App\BookMark;
use App\IpAddress;
use App\ContentAction;
use Illuminate\Support\Facades\Redirect;
use App\PostMedia;
use App\Customer;
use App\Freelancer;
use Illuminate\Support\Facades\Log;
use Request;

class PostHelper {
    /*
      |--------------------------------------------------------------------------
      | PostHelper that contains all the posts related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use post processes
      |
     */

    public static function processPostInputs($inputs) {
        $post_inputs = [
            'freelance_id' => CommonHelper::getFreelancerIdByUuid($inputs['profile_uuid']),
            'caption' => (!empty($inputs['caption'])) ? $inputs['caption'] : "",
            'text' => (!empty($inputs['text'])) ? $inputs['text'] : "",
            'folder_id' => (!empty($inputs['folder_uuid'])) ? CommonHelper::getFolderIdByUuid($inputs['folder_uuid']) : null,
            'url' => (!empty($inputs['url'])) ? $inputs['url'] : null,
            'local_path' => (!empty($inputs['local_path'])) ? $inputs['local_path'] : null,
            'post_type' => $inputs['post_type'],
            'media_type' => $inputs['media_type'],
            'part_no' => (isset($inputs['part_no'])) ? $inputs['part_no'] : null,
            'is_intro' => (isset($inputs['make_into_video'])) ? $inputs['make_into_video'] : 0,
            'post_action_type' => (isset($inputs['post_action_type'])) ? $inputs['post_action_type'] : 'none',
        ];
        return $post_inputs;
    }

    public static function addPost($inputs) {
        $validation = Validator::make($inputs, PostValidationHelper::addPostRules()['rules'], PostValidationHelper::addPostRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        //        if (empty($inputs['image']) && empty($inputs['video'])) {
        //            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['post_media_error']);
        //        }
        Log::channel('daily_change_status')->debug('Inbputs');
        Log::channel('daily_change_status')->debug($inputs);
        $post_inputs = self::processPostInputs($inputs);
        Log::channel('daily_change_status')->debug('post_inputs');
        Log::channel('daily_change_status')->debug($post_inputs);
        if ($inputs['make_into_video'] == 1 && !empty($inputs['folder_uuid'])) {
            $data = ['is_intro' => 0];
            $inputs['folder_id'] = CommonHelper::getFolderIdByUuid($inputs['folder_uuid']);
            $update_post = Post::updatePost('folder_id', $inputs['folder_id'], $data);
            if (!$update_post) {
                DB::rollback();
                return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['update_post_error']);
            }
        }
        Log::channel('daily_change_status')->debug('Before Post save');

        $post = Post::savePost($post_inputs);
        Log::channel('daily_change_status')->debug('Post save');
        Log::channel('daily_change_status')->debug($post);
        if($inputs['type'] == 'subscription_post')
            {
                
                $updated = Freelancer::where('freelancer_uuid',$inputs['profile_uuid'])->update(['has_subscription_content' => 1]);
                Log::channel('daily_change_status')->debug('has_subscription_content == '.$updated);

            }
        if (!$post) {
            DB::rollback();
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['image_upload_error']);
        }
        //        return self::processPostImage($inputs, $post);
        Log::channel('daily_change_status')->debug('processPostMedia');
        return self::processPostMedia($inputs, $post);
    }

    public static function processPostMedia($inputs, $post) {
        $data = [];
        if ($inputs['media_type'] == "image") {
            $validation = Validator::make($inputs, PostValidationHelper::addPostMediaRules()['rules'], PostValidationHelper::addPostMediaRules()['message_' . strtolower($inputs['lang'])]);
            if ($validation->fails()) {
                return CommonHelper::jsonErrorResponse($validation->errors()->first());
            }
            //            MediaUploadHelper::moveSingleS3Image($inputs['image'], CommonHelper::$s3_image_paths['post_image']);
            MediaUploadHelper::moveSingleS3Image($inputs['media'], CommonHelper::$s3_image_paths['post_image']);
            $data = [
                'media_src' => $inputs['media'],
                'post_id' => $post['id'],
                'height' => $inputs['media_height'],
                'width' => $inputs['media_width'],
                'media_type' => $inputs['media_type']
            ];

            //            $save = PostImage::saveNewPostImage($data);
            //            if (!$save) {
            //                DB::rollBack();
            //                return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['image_upload_error']);
            //            }
        }
        Log::channel('daily_change_status')->debug('media_type image');

        //        return self::processPostVideo($inputs, $post);
        if ($inputs['media_type'] == 'video') {
            $validation = Validator::make($inputs, PostValidationHelper::addPostMediaRules()['rules'], PostValidationHelper::addPostMediaRules()['message_' . strtolower($inputs['lang'])]);
            if ($validation->fails()) {
                return CommonHelper::jsonErrorResponse($validation->errors()->first());
            }
            MediaUploadHelper::moveSingleS3Videos($inputs['media'], CommonHelper::$s3_image_paths['post_video']);
            //            if ($inputs['make_into_video'] == 1) {
            //               MediaUploadHelper::moveSingleS3Videos($inputs['media'], CommonHelper::$s3_image_paths['cover_video']);
            //                $check_freelancer = Freelancer::checkFreelancer('freelancer_uuid', $inputs['profile_uuid']);
            //                if (empty($check_freelancer)) {
            //                    return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['empty_profile_error']);
            //                }
            //                $cover_data = ['cover_video' => $inputs['media'], 'cover_video_thumb' => null];
            //                $update_cover = Freelancer::updateFreelancer('freelancer_uuid', $inputs['profile_uuid'], $cover_data);
            //                if (!$update_cover) {
            //                    DB::rollBack();
            //                    return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['video_upload_error']);
            //                }
            //            }
            if (!empty($inputs['media'])) {
                $result = ThumbnailHelper::processThumbnails($inputs['media'], 'post_video', 'freelancer');
                if (!$result['success']) {
                    return CommonHelper::jsonErrorResponse($result['data']['errorMessage']);
                }
            }
            $thumb = explode(".", $inputs['media']);
            $data = [
                'media_src' => $inputs['media'],
                'video_thumbnail' => $thumb[0] . '.jpg',
                'post_id' => $post['id'],
                'height' => $inputs['media_height'],
                'width' => $inputs['media_width'],
                'duration' => $inputs['duration'],
                'media_type' => $inputs['media_type']
            ];
        }
        $save = PostMedia::saveNewPostMedia($data);
        Log::channel('daily_change_status')->debug('save post media');
        if (!$save) {
            DB::rollBack();
            //                return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['video_upload_error']);
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])[$inputs['media_type'] == "image" ? 'image_upload_error' : 'video_upload_error']);
        }
        return self::processPostLocations($inputs, $post);
    }

    public static function processPostImage($inputs, $post) {
        if ($inputs['media_type'] == "image") {
            $validation = Validator::make($inputs, PostValidationHelper::addPostMediaRules()['rules'], PostValidationHelper::addPostMediaRules()['message_' . strtolower($inputs['lang'])]);
            if ($validation->fails()) {
                return CommonHelper::jsonErrorResponse($validation->errors()->first());
            }
            //            MediaUploadHelper::moveSingleS3Image($inputs['image'], CommonHelper::$s3_image_paths['post_image']);
            MediaUploadHelper::moveSingleS3Image($inputs['media'], CommonHelper::$s3_image_paths['post_image']);
            $data = [
                'post_image' => $inputs['media'],
                'post_id' => $post['id'],
                'height' => $inputs['media_height'],
                'width' => $inputs['media_width']
            ];

            $save = PostImage::saveNewPostImage($data);
            if (!$save) {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['image_upload_error']);
            }
        }
        return self::processPostVideo($inputs, $post);
    }

    public static function processPostVideo($inputs, $post) {
        if ($inputs['media_type'] == 'video') {
            $validation = Validator::make($inputs, PostValidationHelper::addPostMediaRules()['rules'], PostValidationHelper::addPostMediaRules()['message_' . strtolower($inputs['lang'])]);
            if ($validation->fails()) {
                return CommonHelper::jsonErrorResponse($validation->errors()->first());
            }
            MediaUploadHelper::moveSingleS3Videos($inputs['media'], CommonHelper::$s3_image_paths['post_video']);
            //            if ($inputs['make_into_video'] == 1) {
            //               MediaUploadHelper::moveSingleS3Videos($inputs['media'], CommonHelper::$s3_image_paths['cover_video']);
            //                $check_freelancer = Freelancer::checkFreelancer('freelancer_uuid', $inputs['profile_uuid']);
            //                if (empty($check_freelancer)) {
            //                    return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['empty_profile_error']);
            //                }
            //                $cover_data = ['cover_video' => $inputs['media'], 'cover_video_thumb' => null];
            //                $update_cover = Freelancer::updateFreelancer('freelancer_uuid', $inputs['profile_uuid'], $cover_data);
            //                if (!$update_cover) {
            //                    DB::rollBack();
            //                    return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['video_upload_error']);
            //                }
            //            }
            if (!empty($inputs['media'])) {
                $result = ThumbnailHelper::processThumbnails($inputs['media'], 'post_video', 'freelancer');
                if (!$result['success']) {
                    return CommonHelper::jsonErrorResponse($result['data']['errorMessage']);
                }
            }
            $thumb = explode(".", $inputs['media']);
            $data = [
                'post_video' => $inputs['media'],
                'video_thumbnail' => $thumb[0] . '.jpg',
                'post_id' => $post['id'],
                'height' => $inputs['media_height'],
                'width' => $inputs['media_width'],
                'duration' => $inputs['duration']
            ];
            $save = PostVideo::saveNewPostVideo($data);
            \Log::info("----------Video saved------");
            \Log::info($save);
            if (!$save) {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['video_upload_error']);
            }
        }
        return self::processPostLocations($inputs, $post);
    }

    public static function processPostLocations($inputs, $post) {

        $location_inputs = [];
        if (!empty($inputs['address'])) {
            $validation = Validator::make($inputs, LocationValidationHelper::addPostLocationRules()['rules'], LocationValidationHelper::addPostLocationRules()['message_' . strtolower($inputs['lang'])]);
            if ($validation->fails()) {
                return CommonHelper::jsonErrorResponse($validation->errors()->first());
            }

            $location_inputs = self::processLocationInputs($inputs, $post);
            $location_inputs['location_uuid'] = UuidHelper::generateUniqueUUID('locations', 'location_uuid');
            unset($location_inputs['post_uuid']);
            $process_location = Location::saveLocation($location_inputs);
            unset($location_inputs['location_uuid']);
            $location_inputs['location_id'] = $process_location->id;
            if (!$process_location) {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['update_location_error']);
            }
            return self::savePostLocation($location_inputs, $post, $inputs);
        }

        $post_details = Post::getPostDetail('post_uuid', $post['post_uuid']);
        Log::channel('daily_change_status')->debug('post_details');
        Log::channel('daily_change_status')->debug($post_details);

        $response = PostResponseHelper::prepareCustomerFeedPostResponse($post_details);
        DB::commit();
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request'], $response);
        //TODO::Ip address table is no more in the database so we commit the code for this
        //        elseif (empty($inputs['address'])) {
        //            $location_data = [];
        //            $key = config("general.ipregistry.ipregistry_key");
        //            $ip = self::getIp();
        //
        //            if (!empty($ip)) {
        //                $check_ip = IpAddress::checkIpAddress('ip_address', $ip);
        //
        //                if (empty($check_ip)) {
        //                    $content = "https://api.ipregistry.co/" . $ip . "?key=" . $key;
        //                    $encoded_data = file_get_contents($content);
        //                    $data = json_decode($encoded_data);
        //                    $location_data = self::prepareLocationData($data);
        //                    $location_inputs = self::processLocationInputs($location_data, $post);
        //                    $ip_data = self::prepareIpAddressData($location_data, $ip);
        //                    $save_ip = IpAddress::saveIpAddress($ip_data);
        //
        //
        //                    if (!$save_ip) {
        //                        DB::rollBack();
        //                        return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['save_location_error']);
        //                    }
        //                    $location_inputs['location_uuid'] = UuidHelper::generateUniqueUUID('locations', 'location_uuid');
        //                    unset($location_inputs['post_uuid']);
        //                    $process_location = Location::saveLocation($location_inputs);
        //                    unset($location_inputs['location_uuid']);
        //                    $location_inputs['location_id'] = $process_location->id;
        //                    if (!$process_location) {
        //                        DB::rollBack();
        //                        return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['update_location_error']);
        //                    }
        //                    return self::savePostLocation($location_inputs, $post, $inputs);
        //                }
        //                $location_data = self::prepareLocationDataIfExist($check_ip);
        //                $location_inputs = self::processLocationInputs($location_data, $post);
        //            }
        //        }
        //
        //        return self::savePostLocation($location_inputs, $post, $inputs);
    }

    public static function prepareLocationDataIfExist($data = []) {
        $location_data = [];
        if (!empty($data)) {
            $location_data['address'] = (!empty($data['city']) ? $data['city'] : "") . " " . (!empty($data['country']) ? $data['country'] : "");
            $location_data['country'] = !empty($data['country']) ? $data['country'] : "";
            $location_data['city'] = !empty($data['city']) ? $data['city'] : "";
            $location_data['added_by'] = "system";
        }
        return $location_data;
    }

    public static function prepareIpAddressData($data = [], $ip = null) {
        $ip_data = [];
        if (!empty($ip)) {
            $ip_data['ip_address'] = !empty($ip) ? $ip : "";
            $ip_data['country'] = !empty($data['country']) ? $data['country'] : "";
            $ip_data['city'] = !empty($data['city']) ? $data['city'] : "";
        }
        return $ip_data;
    }

    public static function prepareLocationData($data = []) {
        $location_data = [];
        if (!empty($data)) {
            $location_data['address'] = (!empty($data->location->city) ? $data->location->city : "") . " " . (!empty($data->location->country->name) ? $data->location->country->name : "");
            $location_data['city'] = !empty($data->location->city) ? $data->location->city : "";
            $location_data['country'] = !empty($data->location->country->name) ? $data->location->country->name : "";
            $location_data['added_by'] = "system";
        }
        return $location_data;
    }

    public static function savePostLocation($location_inputs = [], $post = [], $inputs = []) {
        if (!empty($location_inputs)) {
            $location_inputs['post_id'] = $post['id'];

            $update_location = PostLocation::saveLocation($location_inputs);
            if (!$update_location) {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['save_location_error']);
            }
        }
        $post_details = Post::getPostDetail('post_uuid', $post['post_uuid']);
        $response = PostResponseHelper::prepareCustomerFeedPostResponse($post_details);
        DB::commit();
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request'], $response);
    }

    public static function processLocationInputs($location, $post) {
        //        $inputs['post_location_uuid'] = self::getUniquePostLocationUUID();
        $inputs['post_uuid'] = $post['post_uuid'];
        $inputs['address'] = (!empty($location['address'])) ? $location['address'] : "";
        $inputs['lat'] = (!empty($location['lat'])) ? $location['lat'] : 0;
        $inputs['lng'] = (!empty($location['lng'])) ? $location['lng'] : 0;
        $inputs['street_number'] = (!empty($location['street_number'])) ? $location['street_number'] : "";
        $inputs['route'] = (!empty($location['route'])) ? $location['route'] : "";
        $inputs['city'] = (!empty($location['city'])) ? $location['city'] : "";
        $inputs['state'] = (!empty($location['state'])) ? $location['state'] : "";
        $inputs['country'] = (!empty($location['country'])) ? $location['country'] : "";
        $inputs['country_code'] = (!empty($location['country_code'])) ? $location['country_code'] : "";
        $inputs['zip_code'] = (!empty($location['zip_code'])) ? $location['zip_code'] : "";
        $inputs['place_id'] = (!empty($location['place_id'])) ? $location['place_id'] : "";
        $inputs['added_by'] = (isset($location['added_by'])) ? $location['added_by'] : "user";
        return $inputs;
    }

    public static function getIp() {
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return request()->ip(); // it will return server ip when no client ip found
    }

    //////////////////////////////////////////////////////////// For multiple images and multiple videos
    public static function processPostImages($inputs, $post) {
        if ($inputs['media_type'] != 'images') {
            $images_inputs = [];
            MediaUploadHelper::moveS3Images($inputs['images'], CommonHelper::$s3_image_paths['post_image']);
            foreach ($inputs['images'] as $key => $image) {
                if (!empty($image)) {
                    $images_inputs[$key]['post_image'] = $image['image'];
                    $images_inputs[$key]['post_uuid'] = $post['post_uuid'];
                    $images_inputs[$key]['post_image_uuid'] = self::getUniquePostImageUUID();
                }
            }
            if (!empty($images_inputs)) {
                $save = PostImage::saveMultiplePostImage($images_inputs);
                if (!$save) {
                    DB::rollBack();
                    return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['image_upload_error']);
                }
            }
        }
        return self::processPostVideos($inputs, $post);
    }

    public static function processPostVideos($inputs, $post) {
        if ($inputs['post_type'] != 'text' && !empty($inputs['videos'])) {
            $video_inputs = [];
            MediaUploadHelper::moveS3Videos($inputs['videos'], CommonHelper::$s3_image_paths['post_video']);
            foreach ($inputs['videos'] as $key => $video) {
                if (!empty($video['video'])) {
                    if (empty($video['video_thumbnail'])) {
                        return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['missing_thumbnail_error']);
                    }
                    $video_thumbnail = CommonHelper::uploadSingleImage($video['video_thumbnail'], CommonHelper::$s3_image_paths['video_thumbnail'], $pre_fix = 'thumbnail_', 's3');
                    if (!$video_thumbnail['success']) {
                        return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['thumbnail_upload_error']);
                    }
                    $video_inputs[$key]['video_thumbnail'] = $video_thumbnail['file_name'];
                    $video_inputs[$key]['post_video'] = $video['video'];
                    $video_inputs[$key]['post_uuid'] = $post['post_uuid'];
                    $video_inputs[$key]['post_video_uuid'] = self::getUniquePostImageUUID();
                }
            }
            if (!empty($video_inputs)) {
                $save = PostVideo::saveMultiplePostVideo($video_inputs);
                if (!$save) {
                    DB::rollBack();
                    return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['video_upload_error']);
                }
            }
        }
        //        $get_post = Post::getSinglePost('post_uuid', $post['post_uuid']);
        //        if (!empty($get_post)) {
        //            $followers = Follow::getUserFollowerIds($inputs['user_uuid']);
        //            ProcessNotificationHelper::sendNotificationToFollowers($post, $inputs, $followers);
        //        }
        DB::commit();
        return CommonHelper::jsonSuccessResponseWithData(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_post']);
        //        return self::processPostResponse($inputs, $get_post);
    }

    public static function getUniquePostImageUUID() {
        $data['post_image_uuid'] = Uuid::uuid4()->toString();
        $validation = Validator::make($data, PostValidationHelper::$add_post_image_uuid_rules);
        if ($validation->fails()) {
            $this->getUniquePostImageUUID();
        }
        return $data['post_image_uuid'];
    }

    public static function getUniquePostVideoUUID() {
        $data['post_video_uuid'] = Uuid::uuid4()->toString();
        $validation = Validator::make($data, RulesHelper::$add_post_video_uuid_rules);
        if ($validation->fails()) {
            $this->getUniquePostVideoUUID();
        }
        return $data['post_video_uuid'];
    }

    /////////////////////////////////////////////////////////////////////// multiple images and videos post code ends here

    public static function getPublicProfilePosts($inputs) {

        $validation = Validator::make($inputs, PostValidationHelper::getProfilePostRules()['rules'], PostValidationHelper::getProfilePostRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $offset = !empty($inputs['offset']) ? $inputs['offset'] : null;
        $limit = !empty($inputs['limit']) ? $inputs['limit'] : null;

        $freelanceId = CommonHelper::getRecordByUuid('freelancers', 'freelancer_uuid', $inputs['profile_uuid']);
        $inputs['login_id'] = $freelanceId;
        if ($inputs['login_user_type'] == 'customer') {
            $inputs['login_id'] = CommonHelper::getRecordByUuid('customers', 'customer_uuid', $inputs['logged_in_uuid'], 'id');
        }
        $posts = Post::getPublicFeedProfilePosts('freelance_id', $freelanceId, $limit, $offset);
        $posts_response = [];
        if (!empty($posts)) {
            foreach ($posts as $key => $post) {
                $liked_by_users_ids = [];
                if (!empty($post['likes'])) {
                    foreach ($post['likes'] as $like) {
                        array_push($liked_by_users_ids, $like['liked_by_id']);
                    }
                }
                $likes_count = Like::getLikeCount('post_id', $post['id']);
                $bookmarked_ids = BookMark::getBookMarkedPostIds('customer_id', $inputs['login_id']);
                $data_to_validate = ['liked_by_users_ids' => $liked_by_users_ids, 'bookmarked_ids' => $bookmarked_ids, 'likes_count' => $likes_count];
                $posts_response[$key] = PostResponseHelper::prepareCustomerFeedPostResponse($post, $inputs['login_id'], $data_to_validate);
            }
        }
        //$response = PostResponseHelper::prepareProfilePostResponse($posts);
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request'], $posts_response);
    }

    public static function getProfileSubscription($inputs) {
        $folder_uuid = null;
        $validation = Validator::make($inputs, PostValidationHelper::getProfileSubscriptionRules()['rules'], PostValidationHelper::getProfileSubscriptionRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $posts = [];
        $offset = !empty($inputs['offset']) ? $inputs['offset'] : null;
        $limit = !empty($inputs['limit']) ? $inputs['limit'] : null;
        $inputs['logged_in_id'] = CommonHelper::getRecordByUserType($inputs['login_user_type'], $inputs['logged_in_uuid']);
        $inputs['profile_id'] = CommonHelper::getFreelancerIdByUuid($inputs['profile_uuid']);
        $check_subscription = Subscription::checkSubscriber($inputs['logged_in_id'], $inputs['profile_id']);

        $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['profile_uuid']);
        $folders = Folder::getFolders('freelancer_id', $inputs['freelancer_id']);
        //TODO:: this should be called when login user must be customer

        $bookmarked_ids = [];
        if ($inputs['login_user_type'] != 'freelancer') {
            $customerId = CommonHelper::getCutomerIdByUuid($inputs['logged_in_uuid']);
            $bookmarked_ids = BookMark::getBookMarkedPostIds('customer_id', $customerId);
        }

        $response['is_subscribed'] = $check_subscription;
        if ($inputs['login_user_type'] == "freelancer") {
            if (!empty($folders)) {
                //$inputs['folder_id'] = CommonHelper::getFolderIdByUuid($folders[0]['folder_uuid']);
                $posts = self::getFirstFolderPosts($folders[0]['folder_uuid'], $limit, $offset);
            }
            $response['folders'] = FolderResponseHelper::prepareFolderResponse($folders, $check_subscription);
        } elseif ($inputs['login_user_type'] == "customer" || $inputs['login_user_type'] == "guest") {

            $response['folders'] = FolderResponseHelper::prepareFolderResponseForCustomer($folders, $check_subscription,$customerId,$inputs['currency']);
            if (!empty($response['folders'])) {
                // echo "<pre>";
                // print_r($response['folders'][0]);
                // exit;
                $posts = self::getFirstFolderPosts($response['folders'][0]['folder_uuid'], $limit, $offset);
            }
        }

        $response['posts'] = PostResponseHelper::prepareProfilePostResponse($posts, ['bookmarked_ids' => $bookmarked_ids]);

        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getFirstFolderPosts($folder_uuid = null, $limit = null, $offset = null) {
        $posts = [];

        if (!empty($folder_uuid)) {
            $folderId = CommonHelper::getFolderIdByUuid($folder_uuid);
            $posts = Post::getSubscriptionPosts('folder_id', $folderId, $limit, $offset);
        }
        return $posts;
    }

    public static function getFolderPosts($inputs) {

        $validation = Validator::make($inputs, PostValidationHelper::getFolderPostRules()['rules'], PostValidationHelper::getFolderPostRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $offset = !empty($inputs['offset']) ? $inputs['offset'] : 0;
        $limit = !empty($inputs['limit']) ? $inputs['limit'] : 9;
        $inputs['folder_id'] = CommonHelper::getFolderIdByUuid($inputs['folder_uuid']);
        $posts = Post::getFolderPosts('folder_id', $inputs['folder_id'], $limit, $offset);

        $bookmarked_ids = [];
        if ($inputs['login_user_type'] != 'freelancer') {
            $customerId = CommonHelper::getCutomerIdByUuid($inputs['logged_in_uuid']);
            $bookmarked_ids = BookMark::getBookMarkedPostIds('customer_id', $customerId);
        }

        $response = PostResponseHelper::prepareProfilePostResponse($posts, ['bookmarked_ids' => $bookmarked_ids]);

        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getPostDetail($inputs) {
        if( env('APP_ENV') === 'development')
        {
            if(isset(getallheaders()['Apikey']))
            {
                $apikey = getallheaders()['Apikey'];
            }
            else
            {
                $apikey = null;
            }
        }
        else
        {
            if(isset(getallheaders()['Apikey']))
            {
                $apikey = getallheaders()['Apikey'];
            }
            else
            {
                $apikey = null;
            }
        }
        if ($apikey == null) {
            if (!empty($_SERVER['HTTP_USER_AGENT'])) {
                preg_match("/iPhone|Android|iPad|iPod|webOS|Linux/", $_SERVER['HTTP_USER_AGENT'], $matches);
                $os = current($matches);
                switch ($os) {
                    case 'iPhone':
                        // return Redirect::route('install-app');
                        return redirect('https://apps.apple.com/us/app/circl/id1526462647');
                        break;
                    case 'Android':
                        return Redirect::route('install-app');
                        //                return redirect('https://play.google.com/store/apps');
                        break;
                    case 'iPad':
                        return Redirect::route('install-app');
                        //                return redirect('itms-apps://itunes.apple.com/us');
                        break;
                    case 'iPod':
                        return Redirect::route('install-app');
                        //                return redirect('itms-apps://itunes.apple.com/us');
                        break;
                    case 'webOS':
                        return Redirect::route('install-app');
                        //                return redirect('https://apps.apple.com/us');
                        break;
                    case 'Linux':
                        //                return Route::view('/welcome', 'welcome');
                        return Redirect::route('install-app');
                        //                return redirect('https://apps.apple.com/us');
                        break;
                    default:
                        return Redirect::route('install-app');
                }
            }
        }
        $customerId = null;
        $validation = Validator::make($inputs, PostValidationHelper::getPostDetailRules()['rules'], PostValidationHelper::getPostDetailRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['post_id'] = CommonHelper::getRecordByUuid('posts', 'post_uuid', $inputs['post_uuid']);
        $inputs['customer_id'] = CommonHelper::getRecordByUserType($inputs['login_user_type'], $inputs['logged_in_uuid'], 'user_id');
        if ($inputs['login_user_type'] == 'customer') {
            $customerId = CommonHelper::getCutomerIdByUuid($inputs['logged_in_uuid']);
        }
        $post_detail = Post::getPostDetail('id', $inputs['post_id']);
        if (empty($post_detail)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['empty_post_error']);
        }
        if (!empty($post_detail)) {
            $liked_by_users_ids = [];
            foreach ($post_detail['likes'] as $key => $post) {
                array_push($liked_by_users_ids, $post['liked_by_id']);
            }
        }
        $bookmarked_ids = BookMark::getBookMarkedPostIds('customer_id', $customerId);
        $data_to_validate = ['liked_by_users_ids' => $liked_by_users_ids, 'bookmarked_ids' => $bookmarked_ids];
        $response = PostResponseHelper::preparePostDetailResponse($post_detail, $inputs['customer_id'], $data_to_validate);

        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request'], $response);
    }

    public static function addReportPost($inputs) {
        $validation = Validator::make($inputs, PostValidationHelper::addReportPostRules()['rules'], PostValidationHelper::addReportPostRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['login_id'] = Customer::where('customer_uuid', $inputs['logged_in_uuid'])->where('is_archive', 0)->first()?->id;
        $post = Post::getPostDetail('post_uuid', $inputs['post_uuid']);
        if (empty($post) || empty($inputs['login_id'])) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['report_post_error']);
        }
        $report_post_data = PostDataHelper::makeReportPostArray($inputs, $post);
        $save_reported_post = ReportedPost::addReportPost($report_post_data);
        if (empty($save_reported_post)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['report_post_error']);
        }
        DB::commit();
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request']);
    }

    public static function updatePost($inputs) {
        $validation = Validator::make($inputs, PostValidationHelper::updatePostRules()['rules'], PostValidationHelper::updatePostRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $post_inputs = self::processUpdatePostInputs($inputs);
        $post = Post::updatePost('post_uuid', $inputs['post_uuid'], $post_inputs);
        if (!$post) {
            DB::rollback();
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['post_update_error']);
        }
        DB::commit();
        $post_detail = Post::getPostDetail('post_uuid', $inputs['post_uuid']);
        if (empty($post)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['empty_post_error']);
        }
        if (!empty($post_detail)) {
            $liked_by_users_ids = [];
            foreach ($post_detail['likes'] as $key => $post) {
                if(!empty($post['liked_by_uuid']))
                    array_push($liked_by_users_ids, $post['liked_by_uuid']);
            }
        }
        $bookmarked_ids = [];
        //        $bookmarked_ids = BookMark::getBookMarkedPostIds('customer_id', $inputs['logged_in_uuid']);
        $data_to_validate = ['liked_by_users_ids' => $liked_by_users_ids, 'bookmarked_ids' => $bookmarked_ids];
        $response = PostResponseHelper::preparePostDetailResponse($post_detail, $inputs['logged_in_uuid'], $data_to_validate);
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request'], $response);
        //return self::processUpdatePostImage($inputs, $inputs['post_uuid']);
    }

    public static function processUpdatePostInputs($inputs) {
        $post_inputs = [
            'caption' => (!empty($inputs['caption'])) ? $inputs['caption'] : "",
            'text' => (!empty($inputs['text'])) ? $inputs['text'] : "",
      
            'part_no' => $inputs['part_no'],
        ];
        return $post_inputs;
    }

    public static function processUpdatePostImage($inputs, $post_uuid) {
        if ($inputs['media_type'] == "image") {
            if (!empty($inputs['media'])) {
                MediaUploadHelper::moveSingleS3Image($inputs['media'], CommonHelper::$s3_image_paths['post_image']);
                $data = ['post_image' => $inputs['media']];
                $save = PostImage::createOrUpdatePostImage('post_uuid', $post_uuid, $data);
                if (!$save) {
                    DB::rollBack();
                    return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['image_upload_error']);
                }
            }
        }
        return self::processUpdatePostVideo($inputs, $post_uuid);
    }

    public static function processUpdatePostVideo($inputs, $post_uuid) {
        if ($inputs['media_type'] == 'video') {
            MediaUploadHelper::moveSingleS3Videos($inputs['media'], CommonHelper::$s3_image_paths['post_video']);
            $data = ['post_video' => $inputs['media'], 'video_thumbnail' => null, 'post_uuid' => $post_uuid];
            $save = PostVideo::updatePostVideo('post_uuid', $post_uuid, $data);
            if (!$save) {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['video_upload_error']);
            }
        }
        DB::commit();
        $post = Post::getPostDetail('post_uuid', $post_uuid);
        if (empty($post)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['empty_post_error']);
        }
        $response = PostResponseHelper::preparePostDetailResponse($post);
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request'], $response);
    }

    public static function deletePost($inputs) {
        $validation = Validator::make($inputs, PostValidationHelper::updatePostRules()['rules'], PostValidationHelper::updatePostRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $post_inputs = ['is_archive' => 1];
        $post_data = Post::getPostDetail('post_uuid', $inputs['post_uuid']);
        
        if (empty($post_data)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['invalid_data']);
        }
        if ($post_data['freelance_id'] != $inputs['user_id']) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['invalid_data']);
        }
        if(isset($post_data['folders']) && $post_data['folders']['purchased_premium_folder'] != null)
        {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['preimum_folder_post_purchased']);
        }
        else
        {
            $post = Post::updatePost('post_uuid', $inputs['post_uuid'], $post_inputs);
        }
        //        $post_image = PostImage::updatePostImage('post_uuid', $inputs['post_uuid'], $post_inputs);
        //        $post_video = PostVideo::updatePostVideo('post_uuid', $inputs['post_uuid'], $post_inputs);
        if (!$post) {
            DB::rollback();
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['update_post_error']);
        }
        else
        {
            $freelance_id = $post_data['freelance_id'];
            if($post_data['post_type'] == 'paid' && $post_data['folder_id'] != null)
            {
                $post_count = Post::where('freelance_id',$freelance_id)->whereNotNull('folder_id')->where('is_archive',0)->count();
                if($post_count != 0)
                {
                    Freelancer::where('id',$freelance_id)->update(['has_subscription_content' => 1]);
                }
                else
                {
                    Freelancer::where('id',$freelance_id)->update(['has_subscription_content' => 0]);
                }
            }

        }
        DB::commit();
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request']);
    }

    public static function hideContent($inputs = []) {
        $validation = Validator::make($inputs, PostValidationHelper::hideContentRules()['rules'], PostValidationHelper::hideContentRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['profile_id'] = Customer::where('customer_uuid', $inputs['logged_in_uuid'])->where('is_archive', 0)->first()->user_id;
        if ($inputs['content_type'] == 'post') {
            $post = Post::getPostDetail('post_uuid', $inputs['content_uuid']);
            $inputs['content_id'] = !empty($post['id']) ? $post['id'] : null;
        }
        if (empty($inputs['content_id']) || empty($inputs['profile_id'])) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['hide_content_error']);
        }

        $hide_content_data = PostDataHelper::makeHideContentArray($inputs);
        $check_data = ContentAction::existenceCheck($hide_content_data);
        if (!empty($check_data)) {
            return CommonHelper::jsonErrorResponse('request to hide this content already received');
        }
        $save_hide_content = ContentAction::saveHideContent($hide_content_data);
        if (empty($save_hide_content)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['hide_content_error']);
        }
        DB::commit();
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request']);
    }

    public static function getPost($inputs) {
        if( env('APP_ENV') === 'dev')
        {
            if(isset(getallheaders()['Apikey']))
            {
                $apikey = getallheaders()['Apikey'];
            }
            else
            {
                $apikey = null;
            }
        }
        else
        {
            if(isset(getallheaders()['Apikey']))
            {
                $apikey = getallheaders()['Apikey'];
            }
            else
            {
                $apikey = null;
            }
        }
        if ($apikey == null) {
            preg_match("/iPhone|Android|iPad|iPod|webOS|Linux/", $_SERVER['HTTP_USER_AGENT'], $matches);
            $os = current($matches);
            switch ($os) {
                case 'iPhone':
                    // return Redirect::route('install-app');
                    return redirect('https://apps.apple.com/us/app/circl/id1526462647');
                    break;
                case 'Android':
                    return Redirect::route('install-app');
                    //                return redirect('https://play.google.com/store/apps');
                    break;
                case 'iPad':
                    return Redirect::route('install-app');
                    //                return redirect('itms-apps://itunes.apple.com/us');
                    break;
                case 'iPod':
                    return Redirect::route('install-app');
                    //                return redirect('itms-apps://itunes.apple.com/us');
                    break;
                case 'webOS':
                    return Redirect::route('install-app');
                    //                return redirect('https://apps.apple.com/us');
                    break;
                case 'Linux':
                    //                return Route::view('/welcome', 'welcome');
                    return Redirect::route('install-app');
                    //                return redirect('https://apps.apple.com/us');
                    break;
                default:
                    return Redirect::route('install-app');
            }
        }
        $validation = Validator::make($inputs, PostValidationHelper::addReportPostRules()['rules'], PostValidationHelper::addReportPostRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $post = Post::getPostDetail('post_uuid', $inputs['post_uuid']);
        if (empty($post)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['empty_post_error']);
        }
        $response = PostResponseHelper::preparePostDetailResponse($post);
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request'], $response);
    }

}
