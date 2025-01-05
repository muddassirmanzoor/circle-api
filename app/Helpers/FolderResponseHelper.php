<?php

namespace App\Helpers;

use App\CustomerPremiumFolder;
use App\PurchasedPremiumFolder;

Class FolderResponseHelper {
    /*
      |--------------------------------------------------------------------------
      | FolderResponseHelper that contains all the folder response methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use folder processes
      |
     */

    public static function prepareFolderResponse($data = [], $is_subscribed = false) {
        $response = [];
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $response[$key]['folder_uuid'] = $value['folder_uuid'];
                $response[$key]['profile_uuid'] = CommonHelper::getRecordByUuid('freelancers','id',$value['freelancer_id'],'freelancer_uuid');
                $response[$key]['name'] = $value['name'];
                $response[$key]['type'] = $value['type'];
                $response[$key]['folder_price'] = $value['folder_price'];
                $response[$key]['is_premium'] = $value['is_premium'];
                $response[$key]['image'] = !empty($value['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['folder_images'] . $value['image'] : null;
                if($value['is_premium'] == 1)
                {
                    $response[$key]['is_subscribed'] = false;
                }
                else
                {
                    $response[$key]['is_subscribed'] = $is_subscribed;
                }
            }
        }
        return $response;
    }

    public static function prepareFolderResponseForCustomer($data = [], $is_subscribed = false,$customerId = null,$currency = null) {
        $response = [];
        if (!empty($data)) {
            $key = 0;
            foreach ($data as $count => $value) {
                if (!empty($value['single_post'])) {
                    $response[$key]['folder_uuid'] = $value['folder_uuid'];
                    $response[$key]['profile_uuid'] = CommonHelper::getFreelancerUuidByid( $value['freelancer_id']);
                    $response[$key]['name'] = $value['name'];
                    $response[$key]['type'] = $value['type'];
                    if($currency != null)
                    {
                        $response[$key]['folder_price'] = CommonHelper::getConvertedCurrency($value['folder_price'], $value['currency'], $currency);
                    }
                    else
                    {
                        $response[$key]['folder_price'] = $value['folder_price'];
                    }
                    $response[$key]['is_premium'] = $value['is_premium'];
                    $response[$key]['image'] = !empty($value['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['folder_images'] . $value['image'] : null;
                    $response[$key]['is_subscribed'] = $is_subscribed;
                    $count = PurchasedPremiumFolder::where('folder_id',$value['id'])->where('customer_id',$customerId)->where('payment_status','captured')->count();
                    $response[$key]['is_premium_folder_paid'] = (($count == 0)?0:1);
                    if($value['is_premium'] == 1)
                    {
                        $response[$key]['is_subscribed'] = false;
                    }
                    else
                    {
                        $response[$key]['is_subscribed'] = $is_subscribed;
                    }
                    $key++;
                }
            }
        }
        return array_values($response);
    }

    public static function prepareSingleFolderResponse($value = []) {
        $response = [];
        if (!empty($value)) {

            $response['folder_uuid'] = $value['folder_uuid'];
            $response['profile_uuid'] = CommonHelper::getRecordByUuid('freelancers','id',$value['freelancer_id'],'freelancer_uuid');
            $response['name'] = $value['name'];
            $response['folder_price'] = $value['folder_price'];
            $response['is_premium'] = $value['is_premium'];
            $response['image'] = !empty($value['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['folder_images'] . $value['image'] : null;
        }
        return $response;
    }

}

?>
