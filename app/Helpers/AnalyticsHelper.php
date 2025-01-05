<?php

namespace App\Helpers;

use DB;
use App\Appointment;
use App\FreelancerTransaction;
use Illuminate\Support\Facades\Validator;

class AnalyticsHelper
{
    public static function getAnalyticsResult($inputs = []) {
        $validation = Validator::make($inputs, FreelancerValidationHelper::freelancerUuidRules()['rules'], FreelancerValidationHelper::freelancerUuidRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $response['counts'] = self::getFreelancerAnalyticsCount($inputs);
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }
    
    public static function getFreelancerAnalyticsCount($inputs) { 
        $data = [];
        $data[] = ['title' => 'completed', 'count' => Appointment::getFreelancerAppointmentsCount($inputs['freelancer_uuid'], 'confirmed')];
        $data[] = ['title' => 'pending', 'count' => Appointment::getFreelancerAppointmentsAmount($inputs['freelancer_uuid'], 'pending')];
        $data[] = ['title' => 'cancelled', 'count' => Appointment::getFreelancerAppointmentsCount($inputs['freelancer_uuid'], 'cancelled')];
        $earnings = FreelancerTransaction::calculateEarnings('freelancer_uuid', $inputs['freelancer_uuid']);
        $res = $earnings - ($earnings * (CommonHelper::$circle_commission['commision_rate_percentage'] / 100));
        $data[] = ['title' => 'earnings', 'count' => $res];
        return $data;
    }
}
