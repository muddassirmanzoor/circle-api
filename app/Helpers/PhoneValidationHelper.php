<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Twilio\Rest\Client;

Class PhoneValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | PhoneValidationHelper that contains all the phone methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use phone processes
      |
     */

    public static function phoneValidationRules() {
        $validate['rules'] = [
            'phone_number' => "required",
            'country_code' => "required",
            'country_name' => "required",
            'verification_type' => "required",
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function freelancerPhoneValidationRules() {
        $validate['rules'] = [
//            'phone_number' => "required|unique:freelancers",
//            'phone_number' => "required",
            'country_code' => "required",
            'country_name' => "required",
//            'email' => "required|unique:freelancers",
            'email' => [
                'required',
                Rule::unique('freelancers', 'email')->where('is_archive', 0),
            ],
            'phone_number' => "required|unique:freelancers",
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function emailValidationRules($table = "exceptions") {
        $validate['rules'] = [
//            'email' => "required|unique:" . $table,
            'email' => [
                'required',
                Rule::unique($table, 'email')->where('is_archive', 0),
            ]
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function forgetEmailValidationRules($table = "exceptions") {
        $validate['rules'] = [
//            'email' => "required:" . $table,
            'email' => [
                'required',
                Rule::unique($table, 'email')->where('is_archive', 0),
            ]
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }


    public static function emailCodeValidationRules() {
        $validate['rules'] = [
            'verification_code' => "required",
            'phone_number' => "required",
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function phoneCodeValidationRules() {
        $validate['rules'] = [
            'verification_code' => "required",
            'phone_number' => "required",
            'country_code' => "required",
            'country_name' => "required",
            'verification_type' => "required",
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function forgetPasswordRules() {
        $validate['rules'] = [
            'phone_number' => "required",
            'verification_type' => "required",
            'login_user_type' => "required",
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function resetPasswordRules() {
        $validate['rules'] = [
//            'email' => "required",
            //'code' => "required",
            'phone_number' =>"required",
            'new_password' =>"required",
            'login_user_type' => "required",
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'phone_number.required' => 'Phone number is missing',
            'phone_number.unique' => 'This phone number is already taken',
            'country_code.required' => 'Phone country code is missing',
            'country_name.required' => 'Phone country name is missing',
            'email.required' => 'Email is missing',
            'email.unique' => 'This email has already been taken',
            'verification_type.required' => 'Verification type is missing',
            'verification_code_invalid' => 'Invalid verification code',
            'verifcation_code_sent' => 'Verification code sent successfully!',
            'save_code_error' => 'Code could not be sent',
            'update_code_error' => 'Code could not be processed',
            'code_verifcation_success' => 'Code verified successfully',
            'code_sent_error' => 'Code could not be sent on this number. Please check your number.',
            'verification_code_expires' => 'The verification code has expired. Please send it again.',
            'missing_login_user_type' => 'The user type is missing',
        ];
    }

    public static function arabicMessages() {
        return [
            'phone_number.required' => 'رقم الهاتف مفقود',
            'phone_number.unique' => 'هذا رقم الهاتف مأخوذ بالفعل',
            'country_code.required' => 'Phone country code is missing',
            'country_name.required' => 'Phone country name is missing',
            'email.required' => 'البريد الإلكتروني مفقود',
            'email.unique' => 'وقد تم بالفعل اتخاذ هذا البريد الإلكتروني',
            'verification_type.required' => 'نوع التحقق مفقود',
            'verification_code.required' => 'رمز التحقق مفقود',
            'verification_code_invalid' => 'رمز التحقق غير صالح',
            'verifcation_code_sent' => 'تم إرسال رمز التحقق بنجاح!',
            'save_code_error' => 'لا يمكن إرسال الرمز',
            'update_code_error' => 'لا يمكن معالجة الرمز',
            'code_verifcation_success' => 'تم التحقق من الرمز بنجاح',
            'code_sent_error' => 'لا يمكن إرسال الرمز على هذا الرقم. يرجى التحقق من رقمك.',
            'missing_login_user_type' => 'The user type is missing',
        ];
    }

    /**
     * prepare Message Text method
     * @param type $inputs
     * @param type $code
     * @return string
     */
    public static function prepareMessageText($inputs, $code) {
        if (strtolower($inputs['lang']) == 'ar') {
            $message = 'هو رمز ك ' . $code . ' وستنتهي صلاحيته خلال ساعتين';
        } else {
            $message = $code . ' is your Circl OTP. Do not share it with anyone';
        }
        return $message;
    }

    public static function convert2english($string = "") {
        $newNumbers = range(0, 9);
        // 1. Persian HTML decimal
        $persianDecimal = array('&#1776;', '&#1777;', '&#1778;', '&#1779;', '&#1780;', '&#1781;', '&#1782;', '&#1783;', '&#1784;', '&#1785;');
        // 2. Arabic HTML decimal
        $arabicDecimal = array('&#1632;', '&#1633;', '&#1634;', '&#1635;', '&#1636;', '&#1637;', '&#1638;', '&#1639;', '&#1640;', '&#1641;');
        // 3. Arabic Numeric
        $arabic = array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩');
        // 4. Persian Numeric
        $persian = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');

        $string = str_replace($persianDecimal, $newNumbers, $string);
        $string = str_replace($arabicDecimal, $newNumbers, $string);
        $string = str_replace($arabic, $newNumbers, $string);
        return str_replace($persian, $newNumbers, $string);
    }

    public static function codeRules() {
        $validate['rules'] = [
            'verification_code' => "required|integer",
        ];
        $validate['message_en'] = [];
        $validate['message_ar'] = [];
        return $validate;
    }

    /**
     * get Unique Code method
     * @return conformation code sending to user
     */
    public static function getUniqueCode() {
        $code = self::codeCreation();
        $validation = Validator::make($code, self::codeRules());
        if ($validation->fails()) {
            return self::getUniqueCode();
        } else {
            return $code['verification_code'];
        }
    }

    /**
     *
     * @return type
     */
    public static function codeCreation($digits = 4) {
        $code = rand(pow(10, $digits - 1), pow(10, $digits) - 1);
        return ['verification_code' => $code];
    }

    /**
     * validate Phone Number method
     * @param type $inputs
     * @return string
     */
    public static function validatePhoneNumber($inputs) {
        $response = ['success' => true];
        $phone = $inputs['phone_number'];
        $accountId = config('paths.twillio_account_id');
        $token = config('paths.twillio_auth_token');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://lookups.twilio.com/v1/PhoneNumbers/$phone?Type=carrier&Type=caller-name");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_USERPWD, "$accountId" . ":" . "$token");
        $curlResult = curl_exec($ch);
        if (curl_errno($ch)) {
            $response = ['success' => false, 'message' => $ch];
        }
        curl_close($ch);
        $decode = json_decode($curlResult);
        if (isset($decode->status) && ($decode->status == 404 || $decode->status == '404')) {
            if (strtolower($inputs['lang']) == 'en') {
                $response = ['success' => false, 'message' => 'Invalid phone number'];
            }
            if (strtolower($inputs['lang']) == 'ar') {
                $response = ['success' => false, 'message' => 'رقم الهاتف غير صحيح'];
            }
        }
        return $response;
    }

    /**
     * send single SMS to number
     * @param type $data
     * @return type
     */
    public static function sendSms($data) {
        try {
            $response = ['success' => false, 'message' => 'Code could not be sent on this number. Please check your number.'];
            // Your Account SID and Auth Token from twilio.com/console
            $account_sid = config('paths.twillio_account_id');
            $auth_token = config('paths.twillio_auth_token');
            $twilio_number = config('paths.twilio_number');
            $client = new Client($account_sid, $auth_token);
            $result = $client->messages->create(
                    $data['phone_number'], array(
                'from' => $twilio_number,
                'body' => $data['message'],
                    )
            );
            if ($result) {
                $response = ['success' => true, 'message' => 'Verification code sent successfully'];
            }
            return $response;
        } catch (\Exception $ex) {
            return ['success' => false, 'message' => $ex->getMessage()];
        }
    }

    //return json error response
    public static function prepareVerificationCodeResponse($data = []) {
        $response = [];
        $response['code_uuid'] = !empty($data['code_uuid']) ? $data['code_uuid'] : null;
        $response['profile_uuid'] = !empty($data['profile_uuid']) ? $data['profile_uuid'] : null;
        $response['phone_number'] = !empty($data['phone_number']) ? $data['phone_number'] : null;
        $response['email'] = !empty($data['email']) ? $data['email'] : null;
        $response['verification_code'] = !empty($data['verification_code']) ? $data['verification_code'] : null;
        return $response;
    }

}

?>
