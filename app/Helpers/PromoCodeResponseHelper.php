<?php

namespace App\Helpers;

Class PromoCodeResponseHelper {
    /*
      |--------------------------------------------------------------------------
      | PromoCodeResponseHelper that contains all the Freelancer response methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use Freelancer processes
      |
     */

    public static function preparePromoCodeListResponse($promo_codes = []) {
        $response = [];
        if (isset($promo_codes) && !empty($promo_codes)) {
            foreach ($promo_codes as $key => $promo_code) {
                $response[$key]['code_uuid'] = $promo_code['code_uuid'];
                $response[$key]['coupon_code'] = $promo_code['coupon_code'];
                $response[$key]['valid_from'] = $promo_code['valid_from'];
                $response[$key]['valid_to'] = $promo_code['valid_to'];
                $response[$key]['discount_type'] = $promo_code['discount_type'];
                $response[$key]['coupon_amount'] = (double) $promo_code['coupon_amount'];
            }
        }
        return $response;
    }

    public static function prepareSinglePromoCodeResponse($data = []) {
        $response = null;
        if (!empty($data)) {
            $response['code_uuid'] = $data['code_uuid'];
            $response['coupon_code'] = $data['coupon_code'];
            $response['valid_from'] = $data['valid_from'];
            $response['valid_to'] = $data['valid_to'];
            $response['discount_type'] = $data['discount_type'];
            $response['coupon_amount'] = (double) $data['coupon_amount'];
        }
        return $response;
    }

}

?>
