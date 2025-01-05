<?php

namespace App\Helpers;

Class ActivityValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | ActivityValidationHelper that contains all the activity Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use activity processes
      |
     */

    public static function licenceAgreementRules() {
        $validate['rules'] = [
            'profile_uuid' => 'required',
            'profile_type' => 'required',
            'agree_terms' => 'required',
            'agree_privacy' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'profile_uuid.required' => 'Profile uuid is missing',
            'profile_type.required' => 'Profile type is missing',
            'agree_terms.required' => 'You must agree to our terms and conditions',
            'agree_privacy.required' => 'You must agree to our data privacy policy',
        ];
    }

    public static function arabicMessages() {
        return [
            'profile_uuid.required' => 'الملف الشخصي uuid مفقود',
            'profile_type.required' => 'نوع ملف التعريف مفقود',
            'agree_terms.required' => 'يجب أن توافق على الشروط والأحكام',
            'agree_privacy.required' => 'يجب أن توافق على سياسة خصوصية البيانات لدينا',
        ];
    }

}

?>