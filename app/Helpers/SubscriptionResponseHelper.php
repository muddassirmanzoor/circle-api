<?php

namespace App\Helpers;

Class SubscriptionResponseHelper {
    /*
      |--------------------------------------------------------------------------
      | SubscriptionResponseHelper that contains all the Freelancer response methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use subscriptions processes
      |
     */

    public static function prepareSubscriptionSettingResponse($data = []) {
        $response = [];
        if (!empty($data)) {
            $response['subscription_settings_uuid'] = $data['subscription_settings_uuid'];
            $response['freelancer_uuid'] = $data['freelancer_uuid'];
            $response['type'] = $data['type'];
            $response['price'] = $data['price'];
        }
        return $response;
    }

}

?>
