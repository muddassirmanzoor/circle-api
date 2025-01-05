<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ExceptionHelper;
use App\Helpers\CustomerFeedHelper;
use DB;

/**
 * Description of CustomerController
 *
 * @author ILSA Interactive
 */
class CustomerFeedController extends Controller {

    /**
     * Get customer list method.
     *
     * @return \Illuminate\Http\Response
     */

    public function getProfileWithStories(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            $inputs['login_user_type'] = !empty($inputs['login_user_type']) ? strtolower($inputs['login_user_type']) : "regular";
            if ($inputs['login_user_type'] == "guest") {

                return CustomerFeedHelper::getProfileWithStories($inputs);
            } else {

                return CustomerFeedHelper::getProfileWithStories($inputs);
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function getCustomerFeedPosts(Request $request) {
       try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            $inputs['login_user_type'] = !empty($inputs['login_user_type']) ? strtolower($inputs['login_user_type']) : "regular";

            if ($inputs['login_user_type'] == "guest") {
                return CustomerFeedHelper::getCustomerFeedPosts($inputs);
            } else {
                return CustomerFeedHelper::getCustomerFeedPosts($inputs);
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function getMixedFeedData(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            $inputs['login_user_type'] = !empty($inputs['login_user_type']) ? strtolower($inputs['login_user_type']) : "regular";
            if ($inputs['login_user_type'] == "guest") {
                return CustomerFeedHelper::getMixedFeedData($inputs);
            } else {
                return CustomerFeedHelper::getMixedFeedData($inputs);
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

}
