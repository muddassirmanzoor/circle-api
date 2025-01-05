<?php

namespace App\Helpers;

Class FreelancerMediaHelper {
    /*
      |--------------------------------------------------------------------------
      | FreelancerMediaHelper that contains media related methods for APIs
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
    public static function freelancerProfileMediaProcess($freelancer_inputs, $inputs = []) {
        if (!empty($inputs['profile_image'])) {
            MediaUploadHelper::moveSingleS3Image($inputs['profile_image'], CommonHelper::$s3_image_paths['freelancer_profile_image']);
            $freelancer_inputs['profile_image'] = $inputs['profile_image'];
//            return ['success' => false, 'message' => 'Image could not be uploaded', 'response' => $freelancer_inputs];
        }
        if (!empty($inputs['profile_card_image'])) {
            MediaUploadHelper::moveSingleS3Image($inputs['profile_card_image'], CommonHelper::$s3_image_paths['freelancer_profile_image']);
            $freelancer_inputs['profile_card_image'] = $inputs['profile_card_image'];
//            return ['success' => false, 'message' => 'Image could not be uploaded', 'response' => $freelancer_inputs];
        }
        if (!empty($inputs['cover_image'])) {
            MediaUploadHelper::moveSingleS3Image($inputs['cover_image'], CommonHelper::$s3_image_paths['freelancer_cover_image']);
            $freelancer_inputs['cover_image'] = $inputs['cover_image'];
            $freelancer_inputs['cover_video'] = '';
            $freelancer_inputs['cover_video_thumb'] = '';
//            return ['success' => false, 'message' => 'Image could not be uploaded', 'response' => $freelancer_inputs];
        }
        if (!empty($inputs['cover_video'])) {
            MediaUploadHelper::moveSingleS3Videos($inputs['cover_video'], CommonHelper::$s3_image_paths['cover_video']);
            $freelancer_inputs['cover_video'] = $inputs['cover_video'];
            $freelancer_inputs['cover_image'] = '';
//            return ['success' => false, 'message' => 'Video could not be uploaded', 'response' => $freelancer_inputs];
        }
        if (!empty($inputs['cover_video_thumb'])) {
            MediaUploadHelper::moveSingleS3Image($inputs['cover_video_thumb'], CommonHelper::$s3_image_paths['cover_video']);
            $freelancer_inputs['cover_video_thumb'] = $inputs['cover_video_thumb'];
//            return ['success' => false, 'message' => 'Image could not be uploaded', 'response' => $freelancer_inputs];
        }
        return ['success' => true, 'response' => $freelancer_inputs, 'message' => 'uploaded successfully'];
    }

}

?>