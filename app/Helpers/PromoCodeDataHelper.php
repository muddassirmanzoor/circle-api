<?php

namespace App\Helpers;

Class PromoCodeDataHelper {
    /*
      |--------------------------------------------------------------------------
      | PromoCodeDataHelper that contains all the Freelancer data methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use Freelancer processes
      |
     */

    public static function makePromoCodeArray($input) {
        $freelancer = \App\Freelancer::where('freelancer_uuid', $input['freelancer_uuid'])->where('is_archive', 0)->first();

        if (empty($freelancer)) {
            return [];
        }
        $freelancer_id = $freelancer->id;
        $currency = $freelancer->default_currency;
        $data = array(
            'code_uuid' => UuidHelper::generateUniqueUUID(),
            'freelancer_id' => !empty($input['freelancer_uuid']) ? $freelancer_id : null,
            'coupon_code' => !empty($input['coupon_code']) ? $input['coupon_code'] : null,
            'valid_from' => !empty($input['valid_from']) ? $input['valid_from'] : null,
            'valid_to' => !empty($input['valid_to']) ? $input['valid_to'] : null,
            'discount_type' => !empty($input['discount_type']) ? $input['discount_type'] : null,
            'coupon_amount' => !empty($input['coupon_amount']) ? $input['coupon_amount'] : null,
            'currency' => $currency
        );
        return $data;
    }

    public static function makeSendPromoCodeArray($input) {
        $data = [];

        foreach ($input['customer_uuid'] as $key => $customer_uuid) {
            $data[$key]['client_promocode_uuid'] = UuidHelper::generateUniqueUUID();
            $data[$key]['freelancer_id'] = !empty($input['freelancer_uuid']) ? CommonHelper::getFreelancerIdByUuid($input['freelancer_uuid']) : null;

            $data[$key]['client_id'] = !empty($customer_uuid) ? CommonHelper::getCutomerIdByUuid($customer_uuid) : null;
            $data[$key]['code_id'] = !empty($input['code_uuid']) ? CommonHelper::getPromoCodeIdByUuid($input['code_uuid']) : null;
            $data[$key]['coupon_code'] = !empty($input['coupon_code']) ? $input['coupon_code'] : null;
        }

        return $data;
    }

    public static function promocodeDetailsResponse($data = []) {
        $response = [];
        if (!empty($data)) {
            $response['code_uuid'] = $data['code_uuid'];
            $response['coupon_code'] = $data['coupon_code'];
            $response['valid_from'] = $data['valid_from'];
            $response['valid_to'] = $data['valid_to'];
            $response['discount_type'] = $data['discount_type'];
            $response['coupon_amount'] = $data['coupon_amount'];
        }
        return $response;
    }

}

?>
