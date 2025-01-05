<?php

namespace App\Helpers;

Class FreelancerMessageHelper {
    /*
      |--------------------------------------------------------------------------
      | FreelancerMessageHelper that contains all the Freelancer message methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use Freelancer processes
      |
     */

    public static function getMessageData($type = '', $language = 'EN') {
        if (strtolower($language) == 'ar' && $type == 'error') {
            return self::returnArabicErrorMessage();
        } elseif (strtolower($language) == 'en' && $type == 'error') {
            return self::returnEnglishErrorMessage();
        } elseif (strtolower($language) == 'ar' && $type == 'success') {
            return self::returnArabicSuccessMessage();
        } elseif (strtolower($language) == 'en' && $type == 'success') {
            return self::returnEnglishSuccessMessage();
        }
    }

    public static function returnEnglishSuccessMessage() {
        return [
            'successful_request' => 'Request successful!',
            'update_success' => 'Profile updated successfully!',
            'change_password_success' => 'Password successfully updated'
        ];
    }

    public static function returnArabicSuccessMessage() {
        return [
            'successful_request' => 'طلب ناجح!',
            'update_success' => 'تم تحديث الملف الشخصي بنجاح!',
            'change_password_success' => 'تم تحديث كلمة المرور بنجاح'
        ];
    }

    public static function returnEnglishErrorMessage() {
        return [
            'general_error' => 'Sorry, something went wrong. We are working on getting this fixed as soon as we can',
            'signup_error' => "Sorry, we couldn't register your data",
            'update_settings_error' => "Sorry, settings could not be updated",
            'add_subscription_error' => "Sorry, subscription could not be saved",
            'update_error' => 'Profile could not be updated',
            'sort_code_error' => 'Please enter sort code',
            'change_password_error' => 'Change password failed',
            'same_password_error' => 'New password and old password can not be same',
            'save_category_error' => 'Freelancer category could not be saved',
            'update_package_error' => 'Error occured while updating packages',
            'update_location_error' => 'Error occured while updating location data',
            'category_already_saved' => 'Freelancer category already exists',
            'booking_exists' => 'Category can not be updated, you already have classes or appointments against this category',
            'package_exists' => 'Category can not be updated, you have added packages against this category',
            'invalid_data_error' => 'Invalid data provided',
            'save_schedule_error' => 'Schedule could not be saved',
            'old_password_error' => "Old password is incorrect",
            'already_subscribed_error' => "you have already subscribed",
            'empty_notification_uuid' => "notification uuid is missing",
            'update_notification_error' => "notification status could not be updated",
            'update_schedule_appointment_error' => "You have an appointment scheduled on ",
            'update_schedule_class_error' => "You have a class scheduled on ",
            'schedule_error' => "no schedule available.",
            'freelancer_category_error' => "freelancer category uuid is not correct.",
            'freelancer_time_error' => "Time received from freelancer category is not in good formate.",
            'add_transaction_error' => "Sorry, transaction could not be saved",
            'invalid_service_uuid' => "Freelancer service does not exists",
            'add_transaction_request_due_error' => "Sorry, transaction request dues could not be saved",
            'delete_user' => "Sorry, user cannot be deleted",
            'folder_not_premium' => "Sorry, Folder you have selected is not premium.",
        ];
    }

    public static function returnArabicErrorMessage() {
        return [
            'general_error' => 'وجه الفتاة! حدث خطأ ما. أعد المحاولة من فضلك',
            'signup_error' => "عذرًا ، لم نتمكن من تسجيل بياناتك",
            'update_error' => 'لا يمكن تحديث الملف الشخصي',
            'change_password_error' => 'فشل تغيير كلمة المرور',
            'save_category_error' => 'لا يمكن حفظ فئة المستقل',
            'invalid_data_error' => 'البيانات غير صالحة المقدمة',
            'save_schedule_error' => 'لا يمكن حفظ الجدول',
            'update_settings_error' => "عذرًا ، تعذر حفظ الإعدادات",
            'add_subscription_error' => "عذرًا ، تعذر حفظ الإعدادات",
            'old_password_error' => "Old password is incorrect",
            'already_subscribed_error' => "لقد اشتركت بالفعل",
            'empty_notification_uuid' => "إعلام uuid مفقود",
            'update_notification_error' => "تعذر تحديث حالة الإعلام",
            'update_schedule_appointment_error' => "You have an appointment scheduled on ",
            'update_schedule_class_error' => "You have a class scheduled on ",
            'schedule_error' => "no schedule available.",
            'freelancer_category_error' => "freelancer category uuid is not correct.",
            'same_password_error' => 'New password and old password can not be same',
            'add_transaction_error' => "Sorry, transaction could not be saved",
            'invalid_service_uuid' => "Freelancer service does not exists",
            'freelancer_time_error' => "Time received from freelancer category is not in good formate.",
            'add_transaction_request_due_error' => "Sorry, transaction request dues could not be saved",
            'delete_user' => "عذرا ، لا يمكن حذف المستخدم",

        ];
    }

}

?>
