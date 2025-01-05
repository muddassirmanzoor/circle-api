<?php

namespace App\Helpers;

Class FavouriteValidationHelper {

    public static function favouriteBookmarkRules() {
        $validate['rules'] = [
            'profile_uuid' => 'required',
            'logged_in_uuid' => 'required',
            'local_timezone' => 'required',
            'type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function profileFavouriteRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'customer_uuid' => 'required',
            'logged_in_uuid' => 'required',
            'process_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'freelancer_uuid.required' => 'Freelancer uuid is missing',
            'customer_uuid.required' => 'Customer uuid is missing',
            'profile_uuid.required' => 'Profile uuid is missing',
            'logged_in_uuid.required' => 'logged in user uuid is missing',
            'local_timezone.required' => 'local tiemezone is missing',
            'process_type.required' => 'Type is missing',
            'type.required' => 'Type is missing',
        ];
    }

    public static function arabicMessages() {
        return [
            'freelancer_uuid.required' => 'Freelancer uuid is missing',
            'customer_uuid.required' => 'Customer uuid is missing',
            'profile_uuid.required' => 'Profile uuid is missing',
            'logged_in_uuid.required' => 'logged in user uuid is missing',
            'local_timezone.required' => 'media type is missing',
            'process_type.required' => 'Type is missing',
            'type.required' => 'Type is missing',
        ];
    }

}
