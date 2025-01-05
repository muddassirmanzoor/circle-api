<?php

namespace App\Helpers;

Class PostDataHelper {
    /*
      |--------------------------------------------------------------------------
      | PostDataHelper that contains all the Post data methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use Post processes
      |
     */

    public static function makeReportPostArray($input, $post = []) {
        $data = array(
            'reported_post_uuid' => UuidHelper::generateUniqueUUID(),
            'post_id' => !empty($post['id']) ? $post['id'] : null,
            'reporter_id' => !empty($input['login_id']) ? $input['login_id'] : null,
            'reported_type' => !empty($input['login_user_type']) ? $input['login_user_type'] : null,
            'comments' => !empty($input['comments']) ? $input['comments'] : null,
        );
        return $data;
    }

    public static function makeHideContentArray($input) {
        $data = array(
            'content_action_uuid' => UuidHelper::generateUniqueUUID(),
            'content_id' => !empty($input['content_id']) ? $input['content_id'] : null,
            'content_type' => !empty($input['content_type']) ? $input['content_type'] : null,
            'user_id' => !empty($input['profile_id']) ? $input['profile_id'] : null,
            'is_hidden' => !empty($input['is_hidden']) ? $input['is_hidden'] : 0,
        );
        return $data;
    }

}

?>
