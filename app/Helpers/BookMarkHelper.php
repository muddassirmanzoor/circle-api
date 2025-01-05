<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;
use App\Helpers\CommonHelper;
use App\Helpers\BookMarkValidationHelper;
use App\BookMark;
use App\Post;
use App\Like;
use App\Customer;
use DB;

Class BookMarkHelper {
    /*
      |--------------------------------------------------------------------------
      | BookMarkHelper that contains activity  related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use activity processes
      |
     */

    /**
     * Description of BookMarkHelper
     *
     * @author ILSA Interactive
     */
    public static function addBookmark($inputs) {
        $validation = Validator::make($inputs, BookMarkValidationHelper::addBookMarkRules()['rules'], BookMarkValidationHelper::addBookMarkRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['post_id'] = CommonHelper::getRecordByUuid('posts', 'post_uuid', $inputs['post_uuid']);
        $inputs['customer_id'] = Customer::where('customer_uuid', $inputs['customer_uuid'])->where('is_archive', 0)->first()->id;
        if (strtolower($inputs['type']) == 'remove') {
            return self::removeSavedContent($inputs);
        } elseif (strtolower($inputs['type']) == 'add') {
            $save_book_mark = BookMark::addBookMark($inputs);
            if (!empty($save_book_mark) || array_key_exists('already_exist', $save_book_mark)) {
                DB::commit();
                return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request']);
            }
        }
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('error', $inputs['lang'])['success_error']);
    }

    public static function removeSavedContent($inputs) {
        $delete_book_mark = BookMark::deleteParticularBookMark($inputs['customer_id'], $inputs['post_id']);
        if ($delete_book_mark) {
            DB::commit();
            return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request']);
        }
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('error', $inputs['lang'])['success_error']);
    }

    public static function getSavedContent($inputs) {
        $validation = Validator::make($inputs, BookMarkValidationHelper::getBookMarkRules()['rules'], BookMarkValidationHelper::getBookMarkRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['profile_id'] = CommonHelper::getRecordByUserType($inputs['login_user_type'], $inputs['profile_uuid']);
        $inputs['logged_in_id'] = CommonHelper::getRecordByUserType($inputs['login_user_type'], $inputs['logged_in_uuid']);
        $get_post_ids = BookMark::getBookMarkedPostIds('customer_id', $inputs['profile_id'], 'post_id');

        $limit = !empty($inputs['limit']) ? $inputs['limit'] : null;
        $offset = !empty($inputs['offset']) ? $inputs['offset'] : null;
        $query_params = [];
        if ($inputs['type'] == 'public') {
            $query_params = ['limit' => $limit, 'offset' => $offset, 'post_type' => 'unpaid'];
        } elseif ($inputs['type'] == 'subscriber') {
            $query_params = ['limit' => $limit, 'offset' => $offset, 'post_type' => 'paid'];
        } elseif ($inputs['type'] == 'subscribed') {
            $query_params = ['limit' => $limit, 'offset' => $offset, 'post_type' => 'paid'];
        }
        $posts = Post::getMultiplePosts('id', $get_post_ids, $query_params);

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

                $bookmarked_ids = BookMark::getBookMarkedPostIds('customer_id', $inputs['logged_in_id']);

                $data_to_validate = ['liked_by_users_ids' => $liked_by_users_ids, 'bookmarked_ids' => $bookmarked_ids, 'likes_count' => $likes_count];
                $posts_response[$key] = PostResponseHelper::prepareCustomerFeedPostResponse($post, $inputs['logged_in_uuid'], $data_to_validate);
            }
        }
        //$response = PostResponseHelper::prepareProfilePostResponse($posts);
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request'], $posts_response);
    }

}
