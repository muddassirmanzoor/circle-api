<?php

namespace App\Helpers;

Class MessageHelper {
    /*
      |--------------------------------------------------------------------------
      | MessageHelper that contains all the message methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use message processes
      |
     */

    /**
     * This function selects message language and message type
     *
     */
    public static function getMessageData($type = '', $language = 'EN') {
        $language = strtoupper($language);
        if ($language == 'AR' && $type == 'error') {
            return self::returnArabicErrorMessage();
        } elseif ($language == 'EN' && $type == 'error') {
            return self::returnEnglishErrorMessage();
        } elseif ($language == 'AR' && $type == 'success') {
            return self::returnArabicSuccessMessage();
        } elseif ($language == 'EN' && $type == 'success') {
            return self::returnEnglishSuccessMessage();
        }
    }

    public static function returnEnglishSuccessMessage() {
        return [
            'successful_request' => 'Request successful!',
        ];
    }

    public static function returnArabicSuccessMessage() {
        return [
            'successful_request' => 'طلب ناجح!',
        ];
    }

    public static function returnEnglishErrorMessage() {
        return [
            'general_error' => 'Sorry, something went wrong. We are working on getting this fixed as soon as we can',
            'invalid_data' => 'Invalid data provided',
            'empty_error' => 'Sorry, No record found.',
            'save_location_error' => 'Location could not be saved',
            'update_location_error' => 'Location could not be updated',
            'update_post_error' => 'Post could not be updated',
            'success_error' => 'Unsuccessful request',
            'post_media_error' => 'Image or video is missing for this post',
            'image_upload_error' => 'Image could not be saved',
            'thumbnail_upload_error' => 'video thumbnail could not be saved',
            'missing_thumbnail_error' => 'Video Thumbnail is missing',
            'empty_profile_error' => 'Profile not found',
            'empty_appointment_error' => 'appointment not found',
            'video_upload_error' => 'Video could not be saved',
            'add_folder_error' => 'folder could not be saved',
            'empty_post_error' => 'This post is not available anymore',
            'empty_date_error' => 'Please Select Date',
            'like_exists' => 'You have already liked this post',
            'add_like_error' => 'Like could not be saved',
            'like_not_exists' => 'Like does not exists',
            'report_post_error' => 'post not reported',
            'missing_story_media' => 'No image or video selected for story',
            'story_upload_error' => 'Stories could not be uploaded',
            'remove_story_error' => 'Story could not be deleted',
            'no_class_schedule_available' => 'Swipe through the days above to find a class',
            'no_package_available' => 'Sorry no package available.',
            'add_transaction_log_error' => 'Transaction logs could not be saved',
            'update_promo_code_error' => 'Prono Code could not be updated',
            'update_package_error' => 'Package could not be updated',
            'hide_content_error' => 'Content could not be updated',
            'success_error' => 'Sorry! Request could not be completed',
            'post_update_error' => 'Post could not be updated',
            'preimum_folder_purchased' => 'You can not delete this folder because it has been purchased by someone',
            'preimum_folder_post_purchased' => 'You can not delete this post because its folder has been purchased by someone',
        ];
    }

    public static function returnArabicErrorMessage() {
        return [
            'general_error' => 'وجه الفتاة! حدث خطأ ما. أعد المحاولة من فضلك',
            'invalid_data' => 'البيانات غير صالحة المقدمة',
            'empty_appointment_error' => 'البيانات غير صالحة المقدمة',
            'empty_error' => 'عذرا ، لا يوجد سجل.',
            'save_location_error' => 'لا يمكن حفظ الموقع',
            'update_location_error' => 'Location could not be updated',
            'update_post_error' => 'Post could not be updated',
            'success_error' => 'طلب غير ناجح',
            'post_media_error' => 'الصورة أو الفيديو مفقود لهذه المشاركة',
            'image_upload_error' => 'لا يمكن حفظ الصورة',
            'missing_thumbnail_error' => 'الصورة المصغرة للفيديو مفقودة',
            'thumbnail_upload_error' => 'تعذر حفظ الصورة المصغرة للفيديو',
            'video_upload_error' => 'لا يمكن حفظ الفيديو',
            'empty_profile_error' => 'الملف غير موجود',
            'add_folder_error' => 'تعذر حفظ المجلد',
            'empty_post_error' => 'تعذر حفظ المجلد',
            'empty_date_error' => 'Please Select Date',
            'like_exists' => 'لقد أحببت هذا المنشور بالفعل',
            'add_like_error' => 'لا يمكن حفظ مثل',
            'like_not_exists' => 'مثل غير موجود',
            'missing_story_media' => 'لم يتم تحديد صورة أو فيديو للقصة',
            'story_upload_error' => 'لا يمكن تحميل القصص',
            'remove_story_error' => 'لا يمكن حذف القصة',
            'no_class_schedule_available' => 'Swipe through the days above to find a class',
            'no_package_available' => 'Sorry no package available.',
            'success_error' => 'Sorry! Request could not be completed',
        ];
    }

}

?>
