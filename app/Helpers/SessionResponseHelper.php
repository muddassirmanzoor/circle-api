<?php

namespace App\Helpers;

Class SessionResponseHelper {
    /*
      |--------------------------------------------------------------------------
      | SessionResponseHelper that contains all the session methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use session processes
      |
     */

    public static function freelancerSessionsResponse($data = []) {
        $response = [];
        if (!empty($data)) {
            foreach ($data as $key => $session) {
                $response[$key]['session_uuid'] = $session['session_uuid'];
                $response[$key]['title'] = $session['title'];
                $response[$key]['date'] = $session['session_date'];
                $response[$key]['start_time'] = $session['from_time'];
                $response[$key]['end_time'] = $session['to_time'];
                $response[$key]['price'] = (double) $session['price'];
                $response[$key]['notes'] = $session['notes'];
                $response[$key]['address'] = $session['address'];
            }
        }
        return $response;
    }

}

?>