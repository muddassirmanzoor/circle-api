<?php

namespace App\Helpers;

use App\FreelanceCategory;
use DB;
use Illuminate\Support\Facades\Validator;

Class PriceHelper {
    /*
      |--------------------------------------------------------------------------
      | PriceHelper that contains all the price related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use pricing processes
      |
     */

    /**
     * Description of PriceHelper
     *
     * @author ILSA Interactive
     */
    public static function addFreelancerPricing($inputs = []) {
        if (!empty($inputs)) {
            foreach ($inputs['freelancer_categories'] as $category) {
                $category['freelancer_id'] = CommonHelper::getRecordByUuid('freelancers','freelancer_uuid',$inputs['freelancer_uuid'],'id');
                $validation = Validator::make($category, PriceValidationHelper::saveFreelancerPricingRules()['rules'], PriceValidationHelper::saveFreelancerPricingRules()['message_' . strtolower($inputs['lang'])]);
               
                if ($validation->fails()) {
                    return CommonHelper::jsonErrorResponse($validation->errors()->first());
                }

                $save_price = FreelanceCategory::updateCategories('freelancer_category_uuid', $category['freelancer_category_uuid'], $category);
                if (!$save_price) {
                    DB::rollBack();
                    return CommonHelper::jsonErrorResponse(PriceValidationHelper::saveFreelancerPricingRules()['message_' . strtolower($inputs['lang'])]['save_price_error']);
                }
            }
            return self::freelancerCategoriesProcess($inputs);
        }
        DB::rollBack();
        return CommonHelper::jsonErrorResponse(PriceValidationHelper::saveFreelancerPricingRules()['message_' . strtolower($inputs['lang'])]['empty_data']);
    }

    public static function freelancerCategoriesProcess($inputs = []) {
        $profile_update = ['freelancer_uuid' => $inputs['freelancer_uuid'], 'lang' => $inputs['lang']];
        if (array_key_exists('onboard_count', $inputs) && !empty($inputs['onboard_count'])) {
            $profile_update['onboard_count'] = $inputs['onboard_count'];
        }
        if (array_key_exists('profile_type', $inputs) && !empty($inputs['profile_type'])) {
            $profile_update['profile_type'] = $inputs['profile_type'];
        }
        
        $save_profile = FreelancerHelper::updateFreelancer($profile_update);

        $result = json_decode(json_encode($save_profile));
        if ($result->original->success) {
            DB::commit();
            return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request']);
        }
        DB::rollBack();
        return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['save_price_error']);
    }

}

?>
