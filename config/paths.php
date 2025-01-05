<?php

/*
  |--------------------------------------------------------------------------
  | Paths Configurations
  |--------------------------------------------------------------------------
  |
  | This file will have all paths and URLs for the project.
  |
 */

return [
    // 'key' => 'value',
    'twillio_account_id' => env('TWILLIO_ACCOUNT_ID'),
    'twillio_auth_token' => env('TWILLIO_AUTH_TOKEN'),
    'twilio_number' => env('TWILLIO_NUMBER'),
    's3_cdn_base_url' => env('AWS_CDN_BASE_URL'),
    's3_access_key' => env('AWS_ACCESS_KEY_ID'),
    's3_secret_key' => env('AWS_SECRET_ACCESS_KEY'),
    's3_bucket' => env('AWS_BUCKET'),
    's3_bucket_region' => env('AWS_DEFAULT_REGION'),
    's3_image_path_slug' => 'uploads/general/',
    's3_images_link' => env('AWS_CDN_BASE_URL') . 'uploads/general/',
    'pending' => '60351818728101614092312.png',
    'cancelled' => '603517ede4f3f1614092269.png',
    'confirmed' => '6035180209cd31614092290.png',
    'completed' => '6035180209cd31614092290.png',
];
