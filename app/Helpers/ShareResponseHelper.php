<?php

namespace App\Helpers;

Class ShareResponseHelper {
    /*
      |--------------------------------------------------------------------------
      | ShareResponseHelper that contains all the methods related to share content APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods related to share content processes
      |
     */

    public static function shareContentResponse($inputs = []) {
        $response = [];
        if (!empty($inputs)) {
//            if ($inputs['type'] == "share_post") {
//                $encod_uuid = urlencode($inputs['content_uuid']);
//                $encod_uuid = base64_encode($inputs['content_uuid']);
            $response['post_detail_url'] = config("general.url.url_scheme") . "show_profile?id=" . $inputs['content_uuid'];
//                $response['post_detail_url'] = "com.circlonline.app://show_profile?id=" . $inputs['content_uuid'];
//                $response['post_detail_url'] = "com.circlonline.app://show_profile?id=" . $encod_uuid;
//            }
        }

        return $response;
    }

}

?>