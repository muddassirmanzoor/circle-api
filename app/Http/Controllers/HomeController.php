<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use App\Helpers\HomeScreenHelper;
use App\Helpers\ExceptionHelper;
use App\Helpers\PushNotificationHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller {
    public function runScheduler(Request $request) {
        Log::channel('snsmessage')->info('Request Received');
        Log::channel('snsmessage')->info($request->json()->all());

        //Storage::disk('s3')->put('/logs.txt', json_encode($request->json()->all()));
        Artisan::call('schedule:run');
        return CommonHelper::jsonSuccessResponse("Scheduler Run Successfully");
    }

    public function testPushNotification() {
        print(PushNotificationHelper::testNotifications());
    }
    /**
     * Description of HomeController
     *
     * @author ILSA Interactive
     */
    public function customizeHomeScreen(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return HomeScreenHelper::customizeHomeScreen($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

        /**
     * Description of HomeController
     *
     * @author ILSA Interactive
     */
    public function getSystemSettings(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return HomeScreenHelper::getSystemSettings($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }


    public function deleteUser(Request $request) {
        try {
            // DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return HomeScreenHelper::deleteUser($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }
}
