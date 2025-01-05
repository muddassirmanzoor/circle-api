<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use App\Helpers\ExceptionHelper;
use App\Helpers\PusherHelper;
use App\UserDevice;
use App\Customer;
use App\Freelancer;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Pusher\PushNotifications\PushNotifications;
use Exception;
class PusherAndroidController extends Controller {

    public function tokenProvider(Request $request)
    {
        try {
            DB::beginTransaction();
    
            Log::info('Beams Auth Log:', $request->all());
    
            $loggedUserUUID = $request->input('logged_in_uuid');
            Log::info('<============login user uuid===============>');
            Log::info(print_r($loggedUserUUID, true));
    
            // Assuming PusherHelper is correctly imported and defined
            $beamsClient = PusherHelper::getBeamsClient();
    
            Log::info('==========beams client=========');
            Log::info(print_r($beamsClient, true));
    
            $beamsToken = $beamsClient->generateToken($loggedUserUUID);
    
            Log::info('===========beams token===========');
            Log::info(print_r($beamsToken, true));
    
            $token = $beamsToken['token'];
    
            $profile = Customer::getSingleCustomerDetail('customer_uuid', $loggedUserUUID);
    
            Log::info('<======= customer profile =========>');
            Log::info(print_r($profile, true));
    
            if (empty($profile)) {
                $profile = Freelancer::getFreelancerDetail('freelancer_uuid', $loggedUserUUID);
                Log::info('<======= freelancer profile =========>');
                Log::info(print_r($profile, true));
            }
    
            $userId = $profile ? $profile['user_id'] : null;
    
            Log::info('=========user ID==============');
            Log::info(print_r($userId, true));
    
            $userDevice = UserDevice::where('user_id', $userId)
                ->where('device_type', 'android')
                ->where('is_archive', 0)
                ->first();
    
            if (!$userDevice) {
                UserDevice::createDevice([
                    'user_id' => $userId,
                    'device_type' => 'android',
                    'device_token' => $token,
                    'is_archive' => 0,
                ]);
            } else {
                $userDevice->device_token = $token;
                $userDevice->save();
            }
    
            DB::commit();
    
            return response()->json(array_merge([
                'success' => true
            ], $beamsToken));
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            Log::error('Beams Query Auth Error:', [
                'exception' => $ex,
                'inputs' => $request->all()
            ]);
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error('Beams Auth Error:', [
                'exception' => $ex,
                'inputs' => $request->all()
            ]);
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function sendTestNotification(Request $request) {

//        try {
        Log::info('Beams Send Notification Log:', $request->all());
        $beamsClient = PusherHelper::getBeamsClient();

        $publishResponse = $beamsClient->publishToUsers(
//            array("a3d183bf-8463-4889-b8e6-38ff53ca50eb"),
                array($request->input('logged_in_uuid')),
                array(
                    "fcm" => array(
                        "notification" => array(
                            "title" => "Hi!",
                            "body" => "This is my first Push Notification!"
                        ),
                        "data" => [
                            'data' => [
                                'test' => 1,
                                'string' => 'H'
                            ]
                        ]
                    ),
        ));
        return CommonHelper::jsonSuccessResponse('Success', [
                    'res' => $publishResponse
        ]);
//        } catch (\Exception $e) {
//
//        }
    }

}
