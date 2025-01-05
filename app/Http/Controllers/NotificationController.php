<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use App\Helpers\NotificationHelper;
use App\Helpers\ExceptionHelper;
use Illuminate\Http\Request;
use DB;

class NotificationController extends Controller {
    /*
      |--------------------------------------------------------------------------
      | NotificationController Class
      |--------------------------------------------------------------------------
      |
      | This class will handle all main functions, which are related to profile stories.
      |
     */

    /**
     * Method to get notifications.
     *
     * @param  'Request'
     * @return json data.
     */
    public function getNotifications(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return NotificationHelper::getNotifications($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function getNotificationsBadgeCount(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return NotificationHelper::getNotificationsBadgeCount($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function updateNotificationStatus(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return NotificationHelper::updateNotificationStatus($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

}
