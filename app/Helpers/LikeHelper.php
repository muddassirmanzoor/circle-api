<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Post;
use App\Like;
use App\Customer;

Class LikeHelper {
    /*
      |--------------------------------------------------------------------------
      | LikeHelper that contains all the posts like related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use post like processes
      |
     */

    public static function prepareLikeInputs($inputs = [], $post = []) {
        $post_inputs = [
            'user_id' => CommonHelper::getRecordByUuid('freelancers','id',$post['freelance_id'],'user_id'),
            'post_id' => $post['id'],
            'liked_by_id' => $inputs['liked_by_id']
        ];
        return $post_inputs;
    }

    public static function addPostLike($inputs) {
        $validation = Validator::make($inputs, PostValidationHelper::processPostLikeRules()['rules'], PostValidationHelper::processPostLikeRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        if ($inputs['type'] == 'like') {
            return self::likePost($inputs);
        } elseif ($inputs['type'] == "unlike") {
            return self::postUnlikeProcess($inputs);
        }
    }

    public static function likePost($inputs) {


        if (Like::checkPostLike($inputs['post_id'], $inputs['liked_by_id'])) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['like_exists']);
        }
        $check_post = Post::getPostDetail('post_uuid', $inputs['post_uuid']);
        if (empty($check_post)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['empty_post_error']);
        }
        $data = self::prepareLikeInputs($inputs, $check_post);
        $save = Like::addLike($data);
        if ($save) {
            //like_by_id is the primary key of user table
                //$save['user_id'] is the primary key of user table which we get from freelance_id from post
//            if ($inputs['liked_by_id'] != $check_post['profile_uuid']) {
            if ($inputs['liked_by_id'] != $save['user_id']) {
                ProcessNotificationHelper::sendLikeNotification($inputs, $save);
            }
            DB::commit();
            $count = Like::getLikeCount('post_id', $inputs['post_id']);
            $response['likes_count'] = !empty($count) ? $count : 0;
            return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request'], $response);
        }
        DB::rollback();

        return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['add_like_error']);
    }

    public static function postUnlikeProcess($inputs) {
        $delete_like = Like::checkAndDeletePostLike($inputs['post_id'], $inputs['liked_by_id']);
        if (!$delete_like) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['like_not_exists']);
        }
        DB::commit();
        $count = Like::getLikeCount('post_id', $inputs['post_id']);
        $response['likes_count'] = !empty($count) ? $count : 0;
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getLikes($inputs) {

        $validation = Validator::make($inputs, PostValidationHelper::getLikesRules()['rules'], PostValidationHelper::getLikesRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $offset = !empty($inputs['offset']) ? $inputs['offset'] : 0;
        $limit = !empty($inputs['limit']) ? $inputs['limit'] : 10;
        $inputs['post_id'] = CommonHelper::getRecordByUuid('posts','post_uuid',$inputs['content_uuid']);
        $liked_by_array = Like::getLikes('post_id', $inputs['post_id'], $offset, $limit);
        $response = self::prepareLikedByCustomerResponse($liked_by_array);

        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function prepareLikedByCustomerResponse($data = []) {
        $response = [];
        if (!empty($data)) {
            foreach ($data as $key => $likes) {
                if ($likes['customer']) {
                    $response[$key]['customer_uuid'] = $likes['customer']['customer_uuid'];
                    $response[$key]['name'] = $likes['customer']['first_name'] . ' ' . $likes['customer']['last_name'];
                    $response[$key]['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse($likes['customer']['profile_image']);
                    $response[$key]['liked_date'] = $likes['created_at'];
                }
            }
        }
        return $response;
    }

}

?>
