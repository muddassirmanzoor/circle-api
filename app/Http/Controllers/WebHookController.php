<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Freelancer;

/**
 * Description of WebHookController
 *
 * @author 
 */
class WebHookController extends Controller {

    use \App\Traits\PusherTrait;

    /**
     * handle-presence
     * Web Hook Action
     */
    public function handlePresence() {
        if (isset($_SERVER['HTTP_X_PUSHER_SIGNATURE'])) {
            try {
                \Log::info("=== handlePresence entered===");
                $body = file_get_contents('php://input');
                $webhook_signature = $_SERVER['HTTP_X_PUSHER_SIGNATURE'];
                $expected_signature = hash_hmac('sha256', $body, env('PUSHER_APP_SECRET'), false);
                /**
                 * it will verify security
                 */
                if ($webhook_signature == $expected_signature) {
                    $payload = json_decode($body, true);
                    \Log::info("=== payload===");
                    \Log::info(print_r($payload, true));
                    foreach ($payload['events'] as $event) {
                        \Log::info("=== payload events===");
                        \Log::info(print_r($payload['events'], true));
                        if (strstr($event['channel'], config('general.chat_channel.personal_presence')) != -1) {
                            if ($event['name'] == 'member_added') {
                                $this->checkUserNotDeliveredMessages($event['user_id']);
                            }
                        }
                    }
                }
                header("Status: 200 OK");
            } catch (\Exception $ex) {
                $this->sendExceptionError($ex, $ex->getCode(), 'handlePresence', 0);
                header("Status: 401 Not authenticated");
            }
        } else {
            \Log::info("=== Status: 402 Not authenticated===");
            header("Status: 401 Not authenticated");
        }
    }

    private function checkUserNotDeliveredMessages($user_id) {
        try {
            if (!empty($user_id)) {
                $freelancer = Freelancer::checkFreelancerExistence('freelancer_uuid', $user_id);
                $role = (!empty($freelancer)) ? "freelancer" : "customer";
            }
            $obj = new \App\Helpers\SendUndeliveredMessage($user_id, $role);
            $obj->sendUndeliveredMessages();
        } catch (\Exception $ex) {
            return $this->sendExceptionError($ex, $ex->getCode(), 'checkUserNotDeliveredMessages', $user_id);
        }
    }

    public function checkChannelData(Request $request) {
        try {
            $this->initPusher();
            $inputs = $request->all();
            print_r($inputs);

            $channel_users = $this->pusher->get('/channels/' . $inputs["channel"] . '/users', []);
            if (!empty($channel_users["result"]["users"])) {
                $ids = array_column($channel_users["result"]["users"], "id");
                print_r($ids);
            }
        } catch (\Exception $ex) {
            return $this->sendExceptionError($ex, $ex->getCode(), 'checkUserNotDeliveredMessages');
        }
    }

}
