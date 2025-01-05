<?php

namespace App\Helpers;

use App\Customer;
use App\Freelancer;
use Illuminate\Support\Facades\Validator;
use App\Helpers\DashboardValidationHelper;

Class DashboardHelper {

    public static function getDashboardCounts($inputs = []) {
        $validation = Validator::make($inputs, DashboardValidationHelper::getDashboardCountRules()['rules'], DashboardValidationHelper::getDashboardCountRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $response['counts'] = "0";
        if ($inputs['login_user_type'] == "customer") {
            $inputs['customer_id'] = CommonHelper::getCutomerIdByUuid( $inputs['logged_in_uuid']);
            $response['counts'] = CustomerHelper::getCustomerDashboardcounts($inputs);
        }

        elseif ($inputs['login_user_type'] == "freelancer") {

            $inputs['freelancer_uuid'] = $inputs['logged_in_uuid'];

            $check_freelancer = self::checkFreelancer($inputs);

            $check_freelancer['local_timezone'] = 'Asia/Karachi';


            $response['counts'] = FreelancerHelper::getFreelancerAppointmentcounts($check_freelancer);
        }
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function checkFreelancer($data) {

        $check_freelancer = Freelancer::getFreelancerDetail('freelancer_uuid', $data['freelancer_uuid']);

        if (empty($check_freelancer)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $data['lang'])['invalid_data']);
        }
        if (isset($check_freelancer['freelancer_categories'][0]) && !empty($check_freelancer['freelancer_categories'][0]['currency'])) {
            $inputs['from_currency'] = $check_freelancer['freelancer_categories'][0]['currency'];
        } else {
            $inputs['from_currency'] = $check_freelancer['default_currency'];
        }
        $inputs['to_currency'] = $check_freelancer['default_currency'];
        $inputs['freelancer_uuid'] = $check_freelancer['freelancer_uuid'];
        $inputs['freelancer_id'] = $check_freelancer['id'];
        return $inputs;
    }

}

?>
