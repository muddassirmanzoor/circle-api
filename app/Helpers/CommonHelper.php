<?php

namespace App\Helpers;

use App\CurrencyConversion;
use App\Purchases;
use App\PurchasesTransition;
use App\SESBounce;
use App\SESComplaint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use DateTime;
use DateTimeZone;
use Carbon\Carbon;

Class CommonHelper {
    /*
      |--------------------------------------------------------------------------
      | CommonHelper that contains all the common methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use common processes
      |
     */

    public static $s3_image_paths = [
        'directooo_general' => 'mobileUploads/',
        'mobile_uploads' => 'mobileUploads/',
        'general' => 'uploads/general/',
        'category_image' => 'uploads/category_images/',
        'freelancer_profile_image' => 'uploads/profile_images/freelancers/',
        'freelancer_profile_thumb_1122' => 'uploads/profile_images/freelancers/1122/',
        'freelancer_profile_thumb_420' => 'uploads/profile_images/freelancers/420/',
        'freelancer_profile_thumb_336' => 'uploads/profile_images/freelancers/336/',
        'freelancer_profile_thumb_240' => 'uploads/profile_images/freelancers/240/',
        'freelancer_profile_thumb_96' => 'uploads/profile_images/freelancers/96/',
        'freelancer_cover_image' => 'uploads/cover_images/freelancers/',
        'company_logo' => 'uploads/company_logos/company_logo/',
        'customer_profile_image' => 'uploads/profile_images/customers/',
        'customer_profile_thumb_1122' => 'uploads/profile_images/customers/1122/',
        'customer_profile_thumb_420' => 'uploads/profile_images/customers/420/',
        'customer_profile_thumb_336' => 'uploads/profile_images/customers/336/',
        'customer_profile_thumb_240' => 'uploads/profile_images/customers/240/',
        'customer_profile_thumb_96' => 'uploads/profile_images/customers/96/',
        'customer_cover_image' => 'uploads/cover_images/customers/',
        'cover_video' => 'uploads/videos/cover_videos/',
        'post_image' => 'uploads/posts/post_images/',
        'post_video' => 'uploads/posts/post_videos/',
//        'post_video_thumb' => 'uploads/posts/post_videos_thumb/',
        'post_video_thumb' => 'uploads/posts/post_videos/1212/',
        'package_image' => 'uploads/packages/package_image/',
        'folder_images' => 'uploads/folders/folder_image/',
        'image_stories' => 'uploads/stories/image_stories/',
        'video_stories' => 'uploads/stories/video_stories/',
        'video_story_thumb' => 'uploads/stories/video_story_thumb/',
        'gym_logo' => 'uploads/logos/gym_logo/',
        'class_images' => 'uploads/classes/',
        'freelancer_category_image' => 'uploads/freelancer_category_images/',
        'freelancer_category_video' => 'uploads/freelancer_category_videos/',
        'package_description_video' => 'uploads/packages/package_description_video/',
        'class_description_video' => 'uploads/classes/class_description_video',
        'message_attachments' => 'uploads/message_attachments/',
        'video_thumbnail' => 'uploads/video_thumbnail/',
    ];

    public static function paymentErrors($key = '') {
        \Log::info('===========Error Message key=========');
        \Log::info(print_r($key, true));
        $message = [
            "card_expired" => "Your Card is Expired",
            "card_disabled" => "Card is disabled",
            "card_expiry_month_invalid" => "The two-digit expiry month is invalid.",
            "card_expiry_year_invalid" => "The four-digit expiry year is invalid",
            "card_holder_invalid" => "The cardholder is invalid.",
            "card_not_found" => "The card is not found.",
            "payment_expired" => "The payment has expired.",
            "token_expired" => "The Checkout.com token has expired.",
            "token_invalid" => "The Checkout.com token is invalid.",
            "amount_exceeds_balance" => "The payment amount exceeds the balance.",
            "currency_invalid" => "The three-letter ISO currency code is invalid.",
            "source_id_unknown" => "Environment keys are invalid.",
            "source_id_invalid" => "Invalid payment details provided",
        ];
        $error = !empty($message[$key]) ? $message[$key] : null;
        return !empty($error) ? $error : [];
    }

    public static function getErrorMessages($key = '') {
        \Log::info('===========Error Message key=========');
        \Log::info(print_r($key, true));
        $message = [
            20003 => "The payment failed due to a technical issue. If the issue persists please contact us.",
            20005 => "The payment has been declined by your bank. Declined - Do Not Honour",
            20006 => "Error / Invalid request parameters",
            20012 => "The payment has been declined by your bank due to Invalid transaction",
            20014 => "The payment failed due to Invalid card number",
            20051 => "The payment failed due to Insufficient funds",
            20054 => "The payment failed due to Expired card",
            20055 => "The payment failed due to Incorrect PIN ",
            20057 => "Transaction not permitted to cardholder",
            20059 => "the payment has been declined due to suspected fraud",
            20061 => "the payment has been declined due to Exceeded withdrawal value/amount limits",
            20062 => "the payment has been declined due to Restricted card",
            20063 => "the payment has been declined due to Security violation",
            20065 => "the payment has been declined due to Exceeded Withdrawal Frequency Limit",
            20075 => "the payment has been declined due to Allowable PIN tries exceeded",
            20106 => "the payment has been declined due to Unsupported currency",
            20114 => "the payment has been declined due to Invalid channel or token incorrect",
            20152 => "the payment has been declined due to Initial 3DS transaction not completed within 15 minutes",
            20179 => "the payment has been declined due to invalid card data.",
            20087 => "the payment has been declined due to invalid CVV and/or expiry date",
        ];
        $error = !empty($message[$key]) ? $message[$key] : null;
        return !empty($error) ? $error : [];
    }

    public static $app_email_info = [
        'support_email' => 'adeel.ahmed@ilsainteractive.com',
        'contact_email' => 'adeel.ahmed@ilsainteractive.com',
        'site_title' => 'Circl App',
    ];
    public static $circle_commission = [
        'commision_rate_percentage' => 6,
        'fixed_commision_rate' => 5,
        'hyperpay_fee' => 2.5,
        'withdraw_sar_fee' => 15,
        'withdraw_pound_fee' => 3
    ];

    //return json error response
    public static function jsonErrorResponse($error = "Error while request execution") {
        $response = [];
        $response['success'] = false;
        $response['message'] = $error;
        return response()->json($response);
    }

    // return success response with data
    public static function jsonSuccessResponse($msg = "Request Successful", $data = []) {
        $response = [];
        $response['success'] = true;
        $response['message'] = $msg;
        if (empty($data)) {
            $data = null;
        }
        $response['data'] = $data;
        return response()->json($response);
    }

    // return success response without data
    public static function jsonSuccessResponseWithoutData($msg = "Request Successful") {
        $response = [];
        $response['success'] = true;
        $response['message'] = $msg;
        return response()->json($response);
    }

    /**
     * Convert time to another time zone
     * @param type $time
     * @param type $from_timezone
     * @param type $to_timezone
     * @return type
     */
    public static function convertTimeToTimezone($time, $from_timezone = 'UTC', $to_timezone = 'UTC') {
//dd($time);
        $date = new DateTime($time, new DateTimeZone($from_timezone));
        $date->setTimezone(new DateTimeZone($to_timezone));
        return $date->format('H:i:s'); // 23:30:00
    }

    public static function convertStrToDateAndTime($date) {
        return date('Y-m-d H:i:s', $date);
    }

    public static function convertDateTimeToTimezone($time, $from_timezone = 'UTC', $to_timezone = 'UTC') {

        $date = new DateTime($time, new DateTimeZone($from_timezone));
        $date->setTimezone(new DateTimeZone($to_timezone));
        return $date->format('Y-m-d H:i:s'); // 2020-8-13 23:30:00
    }

    public static function convertTimeToTimezoneDay($time, $from_timezone = 'UTC', $to_timezone = 'UTC') {
        $date = new DateTime($time, new DateTimeZone($from_timezone));
        $date->setTimezone(new DateTimeZone($to_timezone));
        return $date->format('D'); // Mon,Tue,Wed...
    }

    public static function convertTimeToTimezoneDate($time, $from_timezone = 'UTC', $to_timezone = 'UTC') {
        $date = new DateTime($time, new DateTimeZone($from_timezone));
        $date->setTimezone(new DateTimeZone($to_timezone));
        return $date->format('d'); // 1,2,3...
    }

    public static function convertTimeToTimezoneMonth($time, $from_timezone = 'UTC', $to_timezone = 'UTC') {
        $date = new DateTime($time, new DateTimeZone($from_timezone));
        $date->setTimezone(new DateTimeZone($to_timezone));
        return $date->format('M'); // Jan,Feb,March...
    }

    public static function convertTimeToTimezoneTime($time, $from_timezone = 'UTC', $to_timezone = 'UTC') {
        $date = new DateTime($time, new DateTimeZone($from_timezone));
        $date->setTimezone(new DateTimeZone($to_timezone));
        return $date->format('h:i A'); // time in 12 hour format with AM or PM
    }

    public static function getTimeDifferenceInMinutes($from_time, $to_time) {

        $start_time = strtotime($from_time);
        $end_time = strtotime($to_time);
        $mintues = round(abs($end_time - $start_time) / 60, 2);
        return $mintues;
    }

    public static function getTimeDifferenceInHours($from_time, $to_time) {
        $t1 = Carbon::parse($from_time);
        $t2 = Carbon::parse($to_time);
        $diff = $t1->diff($t2);
        return $diff;
    }

    public static function uploadSingleImage($file, $s3_destination, $pre_fix = '', $server = 's3') {
        $full_name = $pre_fix . uniqid() . time() . '.' . $file->getClientOriginalExtension();
        $upload = $file->storeAs($s3_destination, $full_name, $server);
        if ($upload) {
            return ['success' => true, 'file_name' => $full_name];
        }
        return ['success' => false, 'file_name' => ''];
    }

    public static function send_email($template, $data, $attachment = null) {
        $support_email = self::$app_email_info['support_email'];
        $site_title = self::$app_email_info['site_title'];
        $permanentBounceExists = SESBounce::checkIfHardBounce($data['email']);
        $complaintExists = SESComplaint::checkIfNotSpam($data['email']);
        if ($permanentBounceExists || $complaintExists):
            return;
        endif;
//        Mail::send('emails.' . $template, $data, function ($message) use ($support_email, $site_title, $data, $attachment) {
//            $message->from($support_email, $site_title);
//            $message->subject($data['subject']);
//            $message->to($data['email']);
//            if (!empty($attachment)) {
//                $message->attach($attachment, ['as' => 'label.pdf', 'mime' => 'application/pdf']);
//            }
//        });
    }

    /**
     * convertToHash method
     * @param type $string
     * @return return hash encode staring
     */
    public static function convertToHash($string = '') {
        return Hash::make($string);
    }

    // return Database date format
    public static function setDbDateFormat($date, $formate = 'Y-m-d') {
        return date($formate, strtotime($date));
    }

    // return User Selected date format
    public static function setDateFormat($date, $format = "Y-m-d") {
        return date($format, strtotime($date));
    }

    // return Database time format
    public static function setDbTimeFormat($time) {
        return $time;
//        return time('h:i:s', strtotime($time));
    }

    // return User Selected time format
    public static function setUserTimeFormat($time) {
        return $time;
//        return time('h:i:s', strtotime($time));
    }

    public static function checkKeyExist($data) {
        $array = [];
        if (!array_key_exists(0, $data)) {
            $array[0] = $data;
        } else {
            $array = $data;
        }
        return $array;
    }

    public static function datePartial($date) {
        $response = [];
        if (!empty($date)) {
            $response['date'] = date("Y-m-d", strtotime($date));
            $response['year'] = date("Y", strtotime($date));
            $response['month'] = date("m", strtotime($date));
            $response['month_name'] = date("M", strtotime($date));
            $response['day'] = date("d", strtotime($date));
            $response['day_name'] = date("D", strtotime($date));
        }


        return $response;
    }

    public static function getEnglishInteger($integer) {
        $alphabat = '';
        if ($integer == 1) {
            $alphabat = '1st';
        }
        if ($integer == 2) {
            $alphabat = '2nd';
        }
        if ($integer == 3) {
            $alphabat = '3rd';
        }
        if ($integer > 3) {
            $alphabat = $integer . 'th';
        }
        return $alphabat;
    }

    public static function getConvertedCurrency($amount, $from_currency, $to_currency) {
        if ($from_currency !== $to_currency) {
            $exchange_rate = self::getExchangeRate($from_currency, $to_currency);
            $result = $amount * $exchange_rate;
            return round($result, 2);
        } else {
            return round($amount, 2);
        }
    }

    public static function getConvertedCurrencyExtended($amount, $from_currency, $to_currency) {
        if ($from_currency !== $to_currency) {
            $exchange_rate = self::getExchangeRate($from_currency, $to_currency);
            $result = $amount * $exchange_rate;
            return round($result, 9);
        } else {
            return round($amount, 9);
        }
    }

    public static function getExchangeRate($from_currency, $to_currency) {

        $chk = self::checkIfConversionExist($from_currency, $to_currency);

        if (isset($chk['rate'])) {
            return $chk['rate'];
        } else {
            $result = '';
            if ((strtolower($from_currency) == "sar" && strtolower($to_currency) == "sar") || (strtolower($from_currency) == "sar" && strtolower($to_currency) == "pound")) {
                $rate_obj = CommonHelper::currencyConversionRequest("SAR", "GBP");
                $result = isset($rate_obj->rate) ? $rate_obj->rate : config('general.globals.' . "Pound");
            }

            if ((strtolower($from_currency) == "pound" && strtolower($to_currency) == "pound") || (strtolower($from_currency) == "pound" && strtolower($to_currency) == "sar")) {
                $rate_obj = CommonHelper::currencyConversionRequest("GBP", "SAR");
                $result = isset($rate_obj->rate) ? $rate_obj->rate : config('general.globals.' . "SAR");
            }

            $resp = CurrencyConversion::createorUpdate($from_currency, $to_currency, ['rate' => $result]);

            return ($result) ? $result : null;
        }
    }

    public static function checkIfConversionExist($from, $to) {

        $conversion = CurrencyConversion::where('from_currency', $from)->where('to_currency', $to)->where('is_archived', 0)->first();
        if ($conversion) {
            $updatedTime = $conversion->updated_at;
            $chkTime = self::checkDateTimeDifferenceInMinutes($updatedTime);
            if ($chkTime < 60) {
                return $conversion->toArray();
            }
        }

        return false;
    }

    public static function getCurrencyExchangeRate($from_currency, $to_currency, $amount) {
        $fixer_key = config("general.fixer.fixer_key");
        $from_currency = ($from_currency == "Pound") ? "GBP" : $from_currency;
        $to_currency = ($to_currency == "Pound") ? "GBP" : $to_currency;
        $end_point = "convert";
        $get_response = "http://data.fixer.io/api/" . $end_point . "?access_key=" . $fixer_key . "&from=" . $from_currency . "&to=" . $to_currency . "&amount=" . $amount;
        $encoded_data = file_get_contents($get_response);
        $decode_data = json_decode($encoded_data);
        $result = $decode_data->info->rate;
        return ($result) ? $result : null;
    }

    public static function jsonSuccessResponseWithDataArray($msg = "Request Successful", $data = []) {
        $response = [];
        $response['success'] = true;
        $response['message'] = $msg;
        if (empty($data)) {
            $data = [];
        }
        $response['data'] = $data;
        return response()->json($response);
    }

    public static function currencyConversionRequest($from_Currency, $to_Currency) {

        $found = true;
        $get = '';
        $json = '';

        try {
            $get = file_get_contents('http://data.fer.io/api/latest?access_key=' . config('general.fixer.fixer_key') . '&base=' . $from_Currency . '&symbols=' . $to_Currency);
            $json = json_decode($get, true);
        } catch (\Throwable $th) {
            $found = false;
        }

        $exchange_rate = new \stdClass();
        if (isset($json['success']) && $json['success'] && $found && isset($json['rates'][$to_Currency])) {

            $exchange_rate->rate = $json['rates'][$to_Currency];
            $exchange_rate->from = $from_Currency;
            $exchange_rate->to = $to_Currency;
        } else {

            $exchange_rate->rate = (strtolower($from_Currency) == "sar" && strtolower($to_Currency) == "gbp") ? config('general.globals.' . "Pound") : config('general.globals.' . "SAR");
            $exchange_rate->to = $to_Currency;
            $exchange_rate->from = $from_Currency;
            /* try {
              $get = \file_get_contents("http://rate-exchange-1.appspot.com/currency?from=" . $from_Currency . "&to=" . $to_Currency);
              $json = json_decode($get, true);

              $exchange_rate->rate = $json['rate'] ;
              $exchange_rate->to = $json['to'];
              $exchange_rate->from = $json['from'];

              } catch (\Throwable $th) {

              } */
        }

        return $exchange_rate;
    }

    public static function checkDateTimeDifferenceInMinutes($datetime) {

        $datetime1 = strtotime($datetime);
        $datetime2 = strtotime(date('Y-m-d H:i:s'));
        $interval = $datetime2 - $datetime1;
        $minutes = round($interval / 60);

        return $minutes;
    }

    public static function convertDateToTimeZone($date, $from_timezone = 'UTC', $to_timezone = 'UTC') {
        $date = new DateTime($date, new DateTimeZone($from_timezone));
        $date->setTimezone(new DateTimeZone($to_timezone));
        return $date->format('Y-m-d'); // 2020-8-13
    }

    public static function convertSinglemyDateToTimeZone($date, $from_timezone = 'UTC', $to_timezone = 'UTC') {
        $date = new DateTime($date, new DateTimeZone($from_timezone));
        $date->setTimezone(new DateTimeZone($to_timezone));

        return $date->format('Y-m-d H:i:s'); // 2020-8-13
    }

    public static function convertTimeToLocalTimeZone($date, $from_timezone = 'UTC', $to_timezone = 'UTC') {

        $date = new DateTime($date, new DateTimeZone($from_timezone));
        $date->setTimezone(new DateTimeZone($to_timezone));
        return $date->format('H:i:s'); // 13:00:05
    }

    public static function convertMyDBDateIntoLocalDateTime($date, $fromTimeZone, $toTimeZone) {
        $date = self::convertStrToDateAndTime($date);
        return self::convertDateTimeToTimeZone($date, $fromTimeZone, $toTimeZone);
    }

    public static function convertMyDBDateIntoLocalDate($date, $fromTimeZone, $toTimeZone) {
        $date = self::convertStrToDateAndTime($date);
        return self::convertDateToTimeZone($date, $fromTimeZone, $toTimeZone);
    }

    public static function convertMyDBTimeIntoLocalTime($date_time, $fromTimeZone, $toTimeZone) {
        $date = self::convertStrToDateAndTime($date_time);
        return self::convertTimeToLocalTimeZone($date, $fromTimeZone, $toTimeZone);
    }

    public static function getFullDateAndTime($date_time, $fromTimeZone, $toTimeZone) {
        $date = self::convertStrToDateAndTime($date_time);
        return self::convertSinglemyDateToTimeZone($date, $fromTimeZone, $toTimeZone);
    }

    // public static function getRecordByUuid($table, $coloum, $value, $fetchColoum = 'id') {
    //     $result = DB::table($table)->where($coloum, $value)->first();
    //     return ($result) ? $result->$fetchColoum : null;
    // }

    public static function getRecordByUuid($table, $column, $value, $fetchColumn = 'id') {
        try {
            $result = DB::table($table)->where($column, $value)->first();
            return ($result) ? $result->$fetchColumn : null;
        } catch (\Exception $e) {
            // Handle the database query error, log it, and return an appropriate response.
            return null;
        }
    }

    public static function getRecordByUserType($type, $uuid, $fetchColoum = 'id', $coloum = 'freelancer_uuid') {
        if ($type == 'freelancer') {
            return self::getRecordByUuid('freelancers', $coloum, $uuid, $fetchColoum);
        }
        $coloum = ($coloum == 'customer_uuid') ? 'customer_uuid' : $coloum;
        if ($coloum == 'freelancer_uuid') {
            $coloum = 'customer_uuid';
        }
        return self::getRecordByUuid('customers', $coloum, $uuid, $fetchColoum);
    }

    public static function getCutomerIdByUuid($uuid, $fetch = 'id') {

        $result = DB::table('customers')->where('customer_uuid', $uuid)->first();
        return ($result) ? $result->$fetch : null;
    }

    public static function getCutomerUUIDByid($uuid, $fetch = 'customer_uuid') {

        $result = DB::table('customers')->where('id', $uuid)->first();
        return ($result) ? $result->$fetch : null;
    }

    public static function getClassIdByUuid($uuid, $fetch = 'id') {
        $result = DB::table('classes')->where('class_uuid', $uuid)->first();
        return ($result) ? $result->$fetch : null;
    }

    public static function getClassUuidBYId($id, $fetch = 'class_uuid') {
        $result = DB::table('classes')->where('id', $id)->first();
        return ($result) ? $result->$fetch : null;
    }

    public static function getClassSchedulesIdByUuid($uuid, $fetch = 'id') {
        $result = DB::table('class_schedules')->where('class_schedule_uuid', $uuid)->first();
        return ($result) ? $result->$fetch : null;
    }

    public static function getClassSchedulesUuidBid($uuid, $fetch = 'class_schedule_uuid') {
        $result = DB::table('class_schedules')->where('id', $uuid)->first();
        return ($result) ? $result->$fetch : null;
    }

    public static function getFreelancerIdByUuid($uuid, $fetch = 'id') {

        $result = DB::table('freelancers')->where('freelancer_uuid', $uuid)->first();
        return ($result) ? $result->$fetch : null;
    }

    public static function getAppointmentIdByUuid($uuid, $fetch = 'id') {
        $result = DB::table('appointments')->where('appointment_uuid', $uuid)->first();
        return ($result) ? $result->$fetch : null;
    }

    public static function getAppointmentUuidById($uuid, $fetch = 'appointment_uuid') {
        $result = DB::table('appointments')->where('id', $uuid)->first();
        return ($result) ? $result->$fetch : null;
    }

    public static function getFreelancerUuidByid($id, $fetch = 'freelancer_uuid') {
        $result = DB::table('freelancers')->where('id', $id)->first();
        return ($result) ? $result->$fetch : null;
    }

    public static function getFreelancerCategoryIdByUuid($uuid, $fetch = 'id') {
        $result = DB::table('freelancer_categories')->where('freelancer_category_uuid', $uuid)->first();
        return ($result) ? $result->$fetch : null;
    }

    public static function getPackageIdByUuid($uuid, $fetch = 'id') {
        $result = DB::table('packages')->where('package_uuid', $uuid)->first();
        return ($result) ? $result->$fetch : null;
    }

    public static function getPurchasedPackageIdByUuid($uuid, $fetch = 'id') {
        $result = DB::table('purchased_packages')->where('purchased_packages_uuid', $uuid)->first();
        return ($result) ? $result->$fetch : null;
    }

    public static function getPackageuuidByid($uuid, $fetch = 'package_uuid') {
        $result = DB::table('packages')->where('id', $uuid)->first();
        return ($result) ? $result->$fetch : null;
    }

    public static function getPromoCodeIdByUuid($uuid, $fetch = 'id') {
        $result = DB::table('promo_codes')->where('code_uuid', $uuid)->first();
        return ($result) ? $result->$fetch : null;
    }

    public static function getCategoryIdByUuid($uuid, $fetch = 'id') {
        $result = DB::table('categories')->where('category_uuid', $uuid)->first();
        return ($result) ? $result->$fetch : null;
    }

    public static function getSubCategoryIdByUuid($uuid, $fetch = 'id') {
        $result = DB::table('sub_categories')->where('sub_category_uuid', $uuid)->first();
        return ($result) ? $result->$fetch : null;
    }

    public static function getStoryIdByUuid($uuid, $fetch = 'id') {
        $result = DB::table('stories')->where('story_uuid', $uuid)->first();
        return ($result) ? $result->$fetch : null;
    }

    public static function getPostIdByUuid($uuid, $fetch = 'id') {
        $result = DB::table('posts')->where('post_uuid', $uuid)->first();
        return ($result) ? $result->$fetch : null;
    }

    public static function getFolderIdByUuid($uuid, $fetch = 'id') {
        $result = DB::table('folders')->where('folder_uuid', $uuid)->first();
        return ($result) ? $result->$fetch : null;
    }

    public static function getContentIdByUUid($type, $uuid, $fetch = 'id') {
        if ($type == 'appointment') {
            $result = DB::table('appointments')->where('appointment_uuid', $uuid)->first();
        }
        if ($type == 'class') {
            $result = DB::table('classes')->where('class_uuid', $uuid)->first();
        }

        return ($result) ? $result->$fetch : null;
    }

    public static function updatePurchaseAndPurcaseTranistion($record) {
        $purchases = Purchases::where('id', $record['purchase_id'])->update(['status' => $record['purchase_status']]);
        $purchasesTransactions = PurchasesTransition::where('id', $record['purchase_transition_id'])->update(['transaction_status' => $record['purchase_transition_status']]);
        return (!$purchases || !$purchasesTransactions) ? false : true;
    }

    public static function configKeys()
    {
        return [
            'AWS_ACCESS_KEY_ID' => env('AWS_ACCESS_KEY_ID'),
            'AWS_SECRET_ACCESS_KEY' => env('AWS_SECRET_ACCESS_KEY'),
            'AWS_DEFAULT_REGION'=> env('AWS_DEFAULT_REGION'),
            'AWS_BUCKET' =>  env('AWS_BUCKET'),
            'AWS_CDN_BASE_URL' =>  env('AWS_CDN_BASE_URL'),
        ];
    }

}

?>
