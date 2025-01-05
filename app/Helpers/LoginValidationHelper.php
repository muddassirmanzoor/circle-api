<?php

namespace App\Helpers;

Class LoginValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | LoginValidationHelper that contains all the Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use validation processes
      |
     */

    public static function socialLoginRules() {
        $validate['rules'] = [
            'device_token' => 'required',
            'device_type' => 'required',
            'login_user_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function loginRules() {
        $validate['rules'] = [
            'email' => 'required|email',
            'password' => 'required',
            'device_token' => 'required',
            'device_type' => 'required',
            'login_user_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function autoLoginRules() {
        $validate['rules'] = [
            'device_token' => 'required',
            'device_type' => 'required',
            'login_user_type' => 'required',
            'login_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function validationMessages() {
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        $validate['rules'] = [];
        return $validate;
    }

    public static function englishMessages() {
        return [
            'email.required' => 'Email is required',
            'password.required' => 'Password is missing',
            'device_token.required' => 'Device token is required',
            'device_type.required' => 'Device type is required',
            'login_user_type.required' => 'User type is required',
            'login_type.required' => 'Login type is required',
            'deleted_account' => 'This account is inactive',
            'blocked_account' => 'This account is blocked',
            'invalid_login' => 'Invalid login details',
            'login_error' => 'Sorry! Login attempt failed',
            'login_success' => 'Login successfully!',
            'logout_success' => 'Logged out successfully!',
            'logout_validation' => 'Freelancer or Customer UUid is required!',
            'logout_error' => 'loggout unsuccessful!',
        ];
    }

    public static function arabicMessages() {
        return [
            'email.required' => 'البريد الإلكتروني مفقود',
            'email.unique' => 'وقد تم بالفعل اتخاذ هذا البريد الإلكتروني',
            'password.required' => 'كلمة المرور مفقودة',
            'device_token.required' => 'رمز الجهاز مطلوب',
            'device_type.required' => 'نوع الجهاز مطلوب',
            'login_user_type.required' => 'نوع المستخدم مطلوب',
            'deleted_account' => 'هذا الحساب غير نشط',
            'blocked_account' => 'تم حظر هذا الحساب',
            'invalid_login' => 'تفاصيل تسجيل الدخول غير صالحة',
            'login_error' => 'آسف! فشلت محاولة تسجيل الدخول',
            'login_success' => 'تسجيل الدخول بنجاح!',
            'logout_validation' => 'لحسابهم الخاص أو UUid العملاء مطلوب!',
            'logout_error' => 'تسجيل الخروج غير ناجحة!',
            'logout_success' => 'تم تسجيل الخروج بنجاح!',
            'login_type.required' => 'Login type is required',
        ];
    }

}

?>