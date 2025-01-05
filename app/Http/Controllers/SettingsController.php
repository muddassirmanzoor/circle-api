<?php

namespace App\Http\Controllers;

use App\Helpers\SettingsHelper;
use App\Helpers\ExceptionHelper;
use Illuminate\Http\Request;
use DB;

class SettingsController extends Controller {
    /**
     * Description of SettingsController
     *
     * @author ILSA Interactive
     */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function getSettings(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return SettingsHelper::getProfileSetting($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function updateNotificationSettings(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return SettingsHelper::updateNotificationSettings($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function updateChatSettings(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return SettingsHelper::updateChatSettings($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function getUserChatSettings(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return SettingsHelper::getUserChatSettings($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

}
