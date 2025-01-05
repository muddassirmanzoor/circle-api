<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;
use App\Freelancer;

Class FreelancerValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | FreelancerValidationHelper that contains all the Freelancer Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use Freelancer processes
      |
     */

    public static function signupRules() {
        $validate['rules'] = [
            //'first_name' => 'required',
            //'last_name' => 'required',
            'profession' => 'required',
            'company' => 'required',
            'email' => 'required|email|unique:freelancers',
            'phone_number' => 'required',
            'country_code' => 'required',
            'country_name' => 'required',
//            'password' => 'required',
            'device_token' => 'required',
            'device_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function updateSettingRules() {
        $validate['rules'] = [
            'profile_uuid' => 'required',
            'notification_settings_uuid' => 'required',
//            'notification' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function addSubscriptionSettings() {
        $validate['rules'] = [
            'freelancer_id' => 'required',
            'type' => 'required',
            'price' => 'required',
            'currency' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function updateSubscriptionSettings() {
        $validate['rules'] = [
            'subscription_settings_uuid' => 'required',
            'type' => 'required',
            'price' => 'required',
            'currency' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function socialSignupRules() {
        $validate['rules'] = [
            //'first_name' => 'required',
//            'email' => 'required|email|unique:freelancers',
            'device_token' => 'required',
            'device_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function addSubscription() {
        $validate['rules'] = [
            'subscription_settings_uuid' => 'required',
            'subscriber_uuid' => 'required',
            'subscribed_uuid' => 'required',
            'login_user_type' => 'required',
            'currency' => 'required',
//            'transaction_id' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function freelancerUuidRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
                //'currency' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function freelancerProfileRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'currency' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function searchFreelancerRules() {
        $validate['rules'] = [
            'lat' => 'numeric',
            'lng' => 'numeric',
            'start_radius' => 'numeric',
            'end_radius' => 'numeric',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function updateProfileRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function updatePhoneWithPinCode() {
        $validate['rules'] = [
            'phone_number' => 'required',
            'verification_code' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function updateFreeLancerNameRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function phoneValidationRules() {
        $validate['rules'] = [
            'phone_number' => 'required',
            'country_code' => 'required',
            'country_name' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function uniqueFreelancerEmailRules($id = 1) {
        $validate['rules'] = [
            'email' => 'required|unique:freelancers,email,' . $id,
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function freelancerDashboarDetailRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'local_timezone' => 'required',
            'currency' => 'required',
            'logged_in_uuid' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getUserChatSettingRules() {
        $validate['rules'] = [
            'login_user_type' => 'required',
            'logged_in_uuid' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function freelancerChangePasswordRules() {
        $validate['rules'] = [
            'profile_uuid' => 'required',
            'old_password' => 'required',
            'new_password' => 'required',
            'login_user_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function freelancerAddBlockTimeRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
            'local_timezone' => 'required'
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function freelancerDailyScheduleRules() {
        $validate['rules'] = [
            'freelancer_id' => 'required',
            'day' => 'required',
            'from_time' => 'required',
            'to_time' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function freelancerMessages() {
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        $validate['rules'] = [];
        return $validate;
    }

    public static function getFreelancerClientsRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function deleteMediaRules() {
        $validate['rules'] = [
            'logged_in_uuid' => 'required',
            'type' => 'required'
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function uniqueFreelancerPhoneRules() {
        $validate['rules'] = [
            'phone_number' => 'unique:freelancers',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'logged_in_uuid.required' => 'login uuid is missing',
            'profile_uuid.required' => 'profile uuid is missing',
            'setting_uuid.required' => 'settings uuid is missing',
            'subscription_settings_uuid.required' => 'subscription settings uuid is missing',
            'subscriber_uuid.required' => 'subscriber uuid is missing',
            'subscribed_uuid.required' => 'subscribed uuid is missing',
            'subscription_date.required' => 'subscribed uuid is missing',
            'transaction_id.required' => 'Payment transaction id is missing',
            'notification.required' => 'notification bit is missing',
            'first_name.required' => 'First name is missing',
            'last_name.required' => 'Last name is missing',
            'profession.required' => 'Profession is missing',
            'company.required' => 'Company name is missing',
            'email.required' => 'Email is missing',
            'email.unique' => 'This email has already been taken.',
            'phone_number.required' => 'Phone number is missing',
            'phone_number.unique' => 'Phone number already taken',
            'country_code.required' => 'Phone country code is missing',
            'country_name.required' => 'Phone country name is missing',
            'password.required' => 'Password is missing',
            'freelancer_uuid.required' => 'Freelancer uuid is required',
            'customer_uuid.required' => 'Customer uuid is required',
            'appointment_uuid.required' => 'Appointment uuid is required',
            'service_uuid.required' => 'Service uuid is required',
            'device_token.required' => 'Device token is required',
            'device_type.required' => 'Device type is required',
            'day.required' => 'Please specify the day',
            'from_time.required' => 'Please enter start time',
            'to_time.required' => 'Please enter ending time',
            'start_date.required' => 'Start Date is required',
            'end_date.required' => 'End Date is required',
            'start_time.required' => 'Start time is required',
            'end_time.required' => 'End time is required',
            'name.required' => 'Name is required',
            'title.required' => 'Title is required',
            'no_of_students.required' => 'No of Student is required',
            'price.required' => 'Price is required',
            'class_type.required' => 'Class Type is required',
            'class_uuid.required' => 'Class uuid is required',
            'date.required' => 'Date is missing',
            'class_date.required' => 'Date is missing',
            'schedule_type.required' => 'Class schedule type missing',
            'profession_uuid.required' => 'Profession uuid is required',
            'type.required' => 'type is missing',
            'price.required' => 'price is missing',
            'old_password.required' => 'Old password is missing',
            'new_password.required' => 'New password is missing',
            'local_timezone.required' => 'Timezone info is missing',
            'currency.required' => 'Currency is missing',
            'missing_password' => 'Password is missing',
            'subscription_settings_uuid.required' => 'Subscription setting uuid is missing',
            'login_user_type.required' => 'User type is required',
            'missing_profession_uuid' => 'Profession uuid is missing',
            'verification_code.required' => 'Pin Code required for updating phone number',
        ];
    }

    public static function arabicMessages() {
        return [
            'first_name.required' => 'الاسم مفقود',
            'last_name.required' => 'الاسم مفقود',
            'profession.required' => 'المهنة مفقودة',
            'company.required' => 'اسم الشركة مفقود',
            'email.required' => 'البريد الإلكتروني مفقود',
            'email.unique' => 'وقد تم بالفعل اتخاذ هذا البريد الإلكتروني',
            'phone_number.required' => 'رقم الهاتف مفقود',
            'country_code.required' => 'Phone country code is missing',
            'country_name.required' => 'Phone country name is missing',
            'password.required' => 'كلمة المرور مفقودة',
            'freelancer_uuid.required' => 'uuid لحسابهم الخاص هو المطلوب',
            'customer_uuid.required' => 'مطلوب المستخدم uuid',
            'appointment_uuid.required' => 'معرف المستخدم مطلوب uuid',
            'device_token.required' => 'رمز الجهاز مطلوب',
            'device_type.required' => 'نوع الجهاز مطلوب',
            'day.required' => 'يرجى تحديد اليوم',
            'from_time.required' => 'يرجى إدخال وقت البدء',
            'to_time.required' => 'الرجاء إدخال وقت الانتهاء',
            'title.required' => 'العنوان مطلوب',
            'start_date.required' => 'تاريخ البدء مطلوب',
            'end_date.required' => 'تاريخ الانتهاء مطلوب',
            'start_time.required' => 'وقت البدء مطلوب',
            'end_time.required' => 'مطلوب وقت الانتهاء',
            'no_of_students.required' => 'لا يوجد طالب مطلوب',
            'price.required' => 'السعر مطلوب',
            'class_type.required' => 'نوع الفصل مطلوب',
            'class_uuid.required' => 'فئة المستخدم هو المطلوب',
            'date.required' => 'التاريخ مفقود',
            'class_date.required' => 'التاريخ مفقود',
            'schedule_type.required' => 'نوع جدول الفصل الدراسي مفقود',
            'notification.required' => 'بت الإعلام مفقود',
            'setting_uuid.required' => 'إعدادات uuid مفقود',
            'subscription_settings_uuid.required' => 'إعدادات uuid مفقود',
            'subscriber_uuid.required' => 'إعدادات uuid مفقود',
            'subscribed_uuid.required' => 'إعدادات uuid مفقود',
            'subscription_date.required' => 'إعدادات  مفقود',
            'transaction_id.required' => 'Payment transaction id is missing',
            'profile_uuid.required' => 'الملف الشخصي uuid مفقود',
            'type.required' => 'النوع مفقود',
            'price.required' => 'السعر مفقود',
            'old_password.required' => 'Old password is missing',
            'new_password.required' => 'New password is missing',
            'local_timezone.required' => 'Timezone info is missing',
            'currency.required' => 'Currency is missing',
            'missing_password' => 'Password is missing',
            'subscription_settings_uuid.required' => 'Subscription setting uuid is missing',
            'login_user_type.required' => 'User type is required',
            'missing_profession_uuid' => 'Profession uuid is missing',
            'verification_code.required' => 'Pin Code required for updating phone number',
        ];
    }

    public static function processFreelancerInputs($inputs = [], $check_freelancer = []) {
        $freelancer_inputs = ['success' => true, 'message' => 'Successful request', 'data' => []];
        if (!empty($inputs['freelancer_uuid'])) {
            $freelancer_inputs['data']['freelancer_uuid'] = $inputs['freelancer_uuid'];
        }
        if (!empty($inputs['first_name'])) {
            $freelancer_inputs['data']['first_name'] = $inputs['first_name'];
        }
        if (!empty($inputs['last_name'])) {
            $freelancer_inputs['data']['last_name'] = $inputs['last_name'];
        }
        if (!empty($inputs['profession'])) {
            $freelancer_inputs['data']['profession'] = $inputs['profession'];
        }
        if (!empty($inputs['profession_uuid'])) {
            $freelancer_inputs['data']['profession_id'] = CommonHelper::getRecordByUuid('professions','profession_uuid',$inputs['profession_uuid']);
        }
        if (!empty($inputs['company'])) {
            $freelancer_inputs['data']['company'] = $inputs['company'];
        }
        if (!empty($inputs['email'])) {
            $email = !empty($check_freelancer['email']) ? $check_freelancer['email'] : null;
            $update_email = $inputs['email'];
            if (!empty($email) && $inputs['email'] == $email) {
                $update_email = null;
            }
            if (!empty($update_email)) {
                $freelancer_id = Freelancer::pluckFreelancerAttribute('freelancer_uuid', $inputs['freelancer_uuid'], 'id');
                $validation = Validator::make($inputs, FreelancerValidationHelper::uniqueFreelancerEmailRules($freelancer_id[0])['rules'], FreelancerValidationHelper::uniqueFreelancerEmailRules($freelancer_id[0])['message_' . strtolower($inputs['lang'])]);
                if ($validation->fails()) {
                    return ['success' => false, 'message' => $validation->errors()->first(), 'data' => []];
                }
            }
            $freelancer_inputs['data']['email'] = !empty($update_email) ? $update_email : $inputs['email'];
//            $freelancer_id = Freelancer::pluckFreelancerAttribute('freelancer_uuid', $inputs['freelancer_uuid'], 'id');
//            $validation = Validator::make($inputs, FreelancerValidationHelper::uniqueFreelancerEmailRules($freelancer_id[0])['rules'], FreelancerValidationHelper::uniqueFreelancerEmailRules($freelancer_id[0])['message_' . strtolower($inputs['lang'])]);
//            if ($validation->fails()) {
//                return ['success' => false, 'message' => $validation->errors()->first(), 'data' => []];
//            }
//            $freelancer_inputs['data']['email'] = $inputs['email'];
        }
        return self::continueFreelancerInputs($freelancer_inputs, $inputs);
    }

    public static function continueFreelancerInputs($freelancer_inputs, $inputs = []) {
        if (!empty($inputs['dob'])) {
            $freelancer_inputs['data']['dob'] = $inputs['dob'];
        }
        if (!empty($inputs['gender'])) {
            $freelancer_inputs['data']['gender'] = $inputs['gender'];
        }
        if (array_key_exists('bio', $inputs)) {
            if (!empty($inputs['bio'])) {
                $freelancer_inputs['data']['bio'] = $inputs['bio'];
            } else {
                $freelancer_inputs['data']['bio'] = null;
            }
        }
        if (!empty($inputs['onboard_count'])) {
            $freelancer_inputs['data']['onboard_count'] = $inputs['onboard_count'];
        }
        if (!empty($inputs['facebook_id'])) {
            $freelancer_inputs['data']['facebook_id'] = $inputs['facebook_id'];
        }
        if (!empty($inputs['google_id'])) {
            $freelancer_inputs['data']['google_id'] = $inputs['google_id'];
        }
        if (!empty($inputs['apple_id'])) {
            $freelancer_inputs['data']['apple_id'] = $inputs['apple_id'];
        }
        return self::continueFreelancerInputsProcess($freelancer_inputs, $inputs);
    }

    public static function continueFreelancerInputsProcess($freelancer_inputs, $inputs = []) {

        if ((isset($inputs['can_travel']) && $inputs['can_travel'] == 0) || !empty($inputs['can_travel'])) {
            $freelancer_inputs['data']['can_travel'] = $inputs['can_travel'];
        }
        if (!empty($inputs['travelling_distance'])) {
            $freelancer_inputs['data']['travelling_distance'] = $inputs['travelling_distance'];
        }
        if (isset($inputs['travelling_cost_per_km'])) {
            $freelancer_inputs['data']['travelling_cost_per_km'] = $inputs['travelling_cost_per_km'];
        }
        if (isset($inputs['is_business'])) {
            $freelancer_inputs['data']['is_business'] = $inputs['is_business'];
            if ($inputs['is_business'] == 0) {
                $freelancer_inputs['data']['business_name'] = null;
                $freelancer_inputs['data']['business_logo'] = null;
            }
        }
        if (!empty($inputs['business_name'])) {
            $freelancer_inputs['data']['business_name'] = $inputs['business_name'];
        }
        if (!empty($inputs['business_logo'])) {
            $freelancer_inputs['data']['business_logo'] = $inputs['business_logo'];
        }
        if (!empty($inputs['currency'])) {
            $freelancer_inputs['data']['default_currency'] = $inputs['currency'];
        }
        if (!empty($inputs['age'])) {
            $freelancer_inputs['data']['age'] = $inputs['age'];
        }
        if (!empty($inputs['booking_preferences'])) {
            $freelancer_inputs['data']['booking_preferences'] = $inputs['booking_preferences'];
        }
        if (array_key_exists('profile_type', $inputs)) {
            if (!empty($inputs['profile_type'])) {
                $freelancer_inputs['data']['profile_type'] = $inputs['profile_type'];
                if ($inputs['profile_type'] == 3 || $inputs['profile_type'] == 2) {
                    $freelancer_inputs['data']['receive_subscription_request'] = 1;
                }
            }
        }

        if (array_key_exists('receive_subscription_request', $inputs)) {
            if ($inputs['receive_subscription_request'] == 0 || $inputs['receive_subscription_request'] == 1) {
                $freelancer_inputs['data']['receive_subscription_request'] = $inputs['receive_subscription_request'];
            }
        }
        if (array_key_exists('has_subscription', $inputs)) {
            if ($inputs['has_subscription'] == 0 || $inputs['has_subscription'] == 1) {
                $freelancer_inputs['data']['has_subscription'] = $inputs['has_subscription'];
            }
        }
        return $freelancer_inputs;
    }

    public static function servicesRules() {
        $validate['rules'] = [
            'service_uuid' => 'required'
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

}

?>
