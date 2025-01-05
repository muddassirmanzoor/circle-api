<?php

namespace App\Helpers;

Class CustomerMediaHelper {
    /*
      |--------------------------------------------------------------------------
      | customerMediaHelper that contains media related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use media processes
      |
     */

    /**
     * Description of ActivityHelper
     *
     * @author ILSA Interactive
     */
    public static function customerProfileMediaProcess($customer_inputs, $inputs = []) {
       
        if (!empty($inputs['profile_image'])) {
            MediaUploadHelper::moveSingleS3Image($inputs['profile_image'], CommonHelper::$s3_image_paths['customer_profile_image']);
            $customer_inputs['profile_image'] = $inputs['profile_image'];
        }
        if (!empty($inputs['cover_image'])) {
            MediaUploadHelper::moveSingleS3Image($inputs['cover_image'], CommonHelper::$s3_image_paths['customer_cover_image']);
            $customer_inputs['cover_image'] = $inputs['cover_image'];
        }
        if (!empty($inputs['cover_video'])) {
            MediaUploadHelper::moveSingleS3Videos($inputs['cover_video'], CommonHelper::$s3_image_paths['cover_video']);
            $customer_inputs['cover_video'] = $inputs['cover_video'];
        }
        if (!empty($inputs['cover_video_thumb'])) {
            MediaUploadHelper::moveSingleS3Image($inputs['cover_video_thumb'], CommonHelper::$s3_image_paths['cover_video']);
            $customer_inputs['cover_video_thumb'] = $inputs['cover_video_thumb'];
        }
        return ['success' => true, 'response' => $customer_inputs, 'message' => 'uploaded successfully'];
    }

}

?>