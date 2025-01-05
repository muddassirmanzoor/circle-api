<?php

namespace App\Helpers;

use Davibennun\LaravelPushNotification\Facades\PushNotification;
/**
 * All methods related to push notification
 * will be here
 */
//use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;

class BaseProcess {

    /**
     * prepare user device tpkens (ios+android)
     * @param type $tokens
     * @return type
     */
    public function prepareTokens($tokens) {
        $response = ["ios" => [], 'android' => []];
        foreach ($tokens as $key => $token) {
            if (empty($token['device_token'])) {
                continue;
            }
            $response = $this->tokenArr($response, $token);
        }
        return $response;
    }

    /**
     * Prepare token array
     * @param type $response
     * @param type $token
     * @return type
     */
    public function tokenArr($response, $token) {
//        $build = (isset($token["build_version"]) && !empty($token["build_version"])) ? $token["build_version"] : "";
        $insert = ["token" => $token['device_token']];
        if ($token['device_type'] == 'android') {
            $response['android'][] = $insert;
        } else {
            $response['ios'][] = $insert;
        }
        return $response;
    }

    /**
     * prepare push notification user array
     * @param type $user
     * @return type
     */
    public function prepareUser($user = []) {
        $response = [];
        if (!empty($user)) {
            $response['id'] = $user['uid'];
            $response['username'] = $user['username'];
            $response['full_name'] = $user['full_name'];
            $response['picture'] = $user['picture'];
        }
        return $response;
    }
    

}
