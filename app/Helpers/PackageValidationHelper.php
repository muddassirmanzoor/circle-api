<?php

namespace App\Helpers;

Class PackageValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | PackageValidationHelper that contains all the Package Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use Package processes
      |
     */

    public static function englishMessages() {
        return [
            'freelancer_uuid.required' => 'Freelancer uuid is required',
            'package_name.required' => 'Package name is required',
            'package_type.required' => 'Package type is required',
            'sub_category_uuid.required' => 'Subcategory uuid is required',
            'service.required' => 'Service info is required',
            'price.required' => 'Total Price is required',
            'discounted_price.required' => 'Discount Price is required',
            'package_uuid.required' => 'Package id is required',
            'package_save_error' => 'Package could not be saved',
            'package_update_error' => 'Package could not be updated',
            'category_error' => 'Service uuid is not exist agaist this freelancer',
            'class_session_count_error' => "This class doesn't have enough sessions left for the provided number of classes. Please reduce your number of classes.",
            'invalid_data' => 'Invalid data provided',
            'missing_class_uuid' => 'Class uuid is missing',
            'missing_package_type' => 'Package type is missing',
            'missing_freelancer_uuid' => 'Freelancer uuid is missing',
            'missing_customer_uuid' => 'Customer uuid is missing',
            'invalid_freelancer_uuid' => 'Invalid freelancer uuid',
            'invalid_customer_uuid' => 'Invalid customer uuid',
        ];
    }

    public static function arabicMessages() {
        return [
            'freelancer_uuid.required' => 'uuid لحسابهم الخاص هو المطلوب',
            'package_name.required' => 'مطلوب المستخدم uuid',
            'package_type.required' => 'Package type is required',
            'service_uuid.required' => 'Service uuid is required',
            'no_of_session.required' => 'no of session is required',
            'price.required' => 'Total Price is required',
            'discounted_price.required' => 'Discount Price is required',
            'package_uuid.required' => 'Package id is required',
            'sub_category_uuid.required' => 'Subcategory uuid is required',
            'package_save_error' => 'Package could not be saved',
            'package_update_error' => 'Package could not be updated',
            'category_error' => 'Service uuid is not exist agaist this freelancer',
            'class_session_count_error' => "This class doesn't have enough sessions left for the provided number of classes. Please reduce your number of classes.",
            'invalid_data' => 'Invalid data provided',
            'missing_class_uuid' => 'Class uuid is missing',
            'missing_package_type' => 'Package type is missing',
            'missing_freelancer_uuid' => 'Freelancer uuid is missing',
            'missing_customer_uuid' => 'Customer uuid is missing',
            'invalid_freelancer_uuid' => 'Invalid freelancer uuid',
            'invalid_customer_uuid' => 'Invalid customer uuid',
        ];
    }

    public static function addSessionPackageRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'package_type' => 'required',
            'service_uuid' => 'required',
            'sub_category_uuid' => 'required',
            'no_of_session' => 'required',
            'price' => 'required',
            'currency' => 'required',
            'discounted_price' => 'required',
            'package_validity' => 'required',
            'validity_type' => 'required',
                //'package_description' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function addClassPackageRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'package_type' => 'required',
//            'service_uuid' => 'required',
//            'sub_category_uuid' => 'required',
            'no_of_session' => 'required',
//            'price' => 'required',
            'currency' => 'required',
//            'discounted_price' => 'required',
            'package_validity' => 'required',
            'validity_type' => 'required',
                //'package_description' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function updatePackageRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'package_uuid' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getAllPackagesRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'currency' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getPurchasedPackagesRules() {
        $validate['rules'] = [
            'currency' => 'required',
            'local_timezone' => 'required',
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function purchasedPackageDetailRules() {
        $validate['rules'] = [
            'package_uuid' => 'required',
            'customer_uuid' => 'required',
            'freelancer_uuid' => 'required',
            'currency' => 'required',
            'local_timezone' => 'required',
//            'logged_in_uuid' => 'required',
//            'login_user_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function sessionPackageDetailRules() {
        $validate['rules'] = [
            'appointment_uuid' => 'required',
            'package_uuid' => 'required',
            'purchase_time' => 'required',
            'customer_uuid' => 'required',
            'freelancer_uuid' => 'required',
            'currency' => 'required',
            'local_timezone' => 'required',
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function classPackageDetailRules() {
        $validate['rules'] = [
            //'class_uuid' => 'required',
            'class_schedule_uuid' => 'required',
            'package_uuid' => 'required',
            'purchase_time' => 'required',
            'freelancer_uuid' => 'required',
            'currency' => 'required',
            'local_timezone' => 'required',
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getPackageDetailRules() {
        $validate['rules'] = [
            'package_uuid' => 'required',
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
            'local_timezone' => 'required',
            'customer_uuid' => 'required',
            'currency' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getPackageBasicDetailRules() {
        $validate['rules'] = [
            'package_uuid' => 'required',
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
            'local_timezone' => 'required',
            'currency' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function deletePackageRules() {
        $validate['rules'] = [
            'package_uuid' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

}

?>
