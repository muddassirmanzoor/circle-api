<?php

/**
 * Description of CommonTrait
 *
 * @author sajid ali
 */

namespace App\Traits;

use Pusher\Pusher;
use App\Helpers\ProcessNotificationHelper;
use App\Message;

trait PusherTrait {

    /**
     * Initialize pusher object
     */
    public function initPusher() {
        $options = array(
            'cluster' => env('PUSHER_APP_CLUSTER'),
            'encrypted' => true
        );

        $this->pusher = new Pusher(
                env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET'), env('PUSHER_APP_ID'), $options
        );
        $obj = (array) $this->pusher;
        return $this->pusher;
    }

    /**
     * Initialize Notification process object
     */
    public function initNotification() {
        return new \App\Helpers\NotificationProcess();
    }

    /**
     * trigger message sent event
     * @param type $channel_name
     * @param type $event_name
     * @param type $event_data
     * @param type $receiver
     * @param type $loginUser
     * @return type
     */
    public function triggerPusher($trigger, $receiver, $loginUser, $isCallback = false) {
        $this->initPusher();
        $oneToOne = $trigger['channel'];

        if ($sent_channel = $this->getChannelNameForBroadCast($oneToOne, $receiver)) {
            \Log::info("Final pusher send channel --->" . $sent_channel);
            $this->sentStatus = (strpos($sent_channel, config('general.chat_channel.one_to_one')) !== false) ? "V" : "D";
            \Log::info("Final pusher send channel --->" . $this->sentStatus);
            \Log::info("<-------- Login user id --->" . $loginUser['uuid']);
            \Log::info("<-------- Receiver user id --->" . $receiver['uuid']);
            $status = ($this->sentStatus == "V") ? "viewed" : "delivered";
            \Log::info("=================== Message Status is ===========================");
            \Log::info(print_r($status, true));
            $update_status = Message::updateStatus('message_uuid', $trigger['data']['message_uuid'], $status);
//            $update_status = Message::updateStatus($loginUser['uuid'], $receiver['uuid'], $status);
            \Log::info("<-------- Update Status --->" . $update_status);

            \Log::info("------- Message Status--------");
            \Log::info($this->sentStatus);
            // creating trigger data copy
            \Log::info($trigger['event']);
            \Log::info($trigger['data']);
            $trigger['data']['status'] = $status;
            return $this->pusher->trigger($sent_channel, $trigger['event'], array("data" => $trigger['data']), null, true);
        }
//         No one is present send push notification
        return ProcessNotificationHelper::sendMessageNotification($trigger['data'], $receiver, $loginUser, $isCallback);

//        return $this->triggerNotification($trigger['data'], $receiver, $loginUser, $isCallback);
    }

    private function getChannelName() {

    }

    public function prepareChatData($data) {
        $data["sender"] = [
            "id" => $data["sender_id"],
            "name" => $data["sender_name"],
            "username" => $data["sender_username"],
            "profile_image" => $data["sender_picture"]
        ];
        unset($data["sender_id"]);
        unset($data["sender_name"]);
        unset($data["sender_username"]);
        unset($data["sender_picture"]);
        return $data;
    }

    /**
     *
     * @param type $oneToOne
     * @param type $receiver
     * @return boolean
     */
    public function getChannelNameForBroadCast($oneToOne, $receiver) {
        //$channel one to one
        if ($this->getReceiverAvailabityInChannel($oneToOne, $receiver["uuid"])) {
            return $oneToOne;
        } else {
            $personel_presence_channel = config("general.chat_channel.personal_presence") . $receiver['uuid'];
            if ($this->getReceiverAvailabityInChannel($personel_presence_channel, $receiver["uuid"])) {
                return $personel_presence_channel;
            }
        }
        return false;
    }

    /**
     *
     * @param type $channel
     * @param type $uid
     */
    private function getReceiverAvailabityInChannel($channel, $uid) {
        $channel_users = $this->pusher->get('/channels/' . $channel . '/users', []);
        \Log::info("-- Received ID---" . $uid);
        \Log::info("--- HERE Channel users list ---" . $channel);
        \Log::info(print_r($channel_users, true));
        if(!empty($channel_users->result)) {
            if (!empty($channel_users["result"]["users"])) {
                $ids = array_column($channel_users["result"]["users"], "id");
                return in_array($uid, $ids);
            }
        }
        return false;
    }

    /*     * checkUserPresenceInChannel
     * check user available in channel users lis$checkAnotherChannet
     * @param type $channel_users
     * @param type $receiver
     * @return boolean
     */

    public function checkUserInChannelUsersList($channel_users, $receiver) {
        if (!empty($channel_users['status']) == 200 && !empty($channel_users['result']['users'])) {
            $users = array_column($channel_users['result']['users'], 'id');
            if (in_array($receiver['id'], $users)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Trigger push notification
     * @param type $event_data
     * @param type $receiver
     * @param type $loginUser
     * @param type $isCallback
     * @return boolean
     */
    public function triggerNotification($event_data, $receiver, $loginUser, $isCallback = false) {
        $object = $this->initNotification();
        /**
         * Is Call Back = True send By Receiver that I have received the message
         */
        if ($isCallback) {
            if ($receiver = \App\UserDevice::getUserDevice("user_id", $receiver['uuid'])) {
                return $object->sendCallBackNotification($event_data, $receiver);
            }
            return true;
        }
//        if ($receiver = \App\UserDevice::getUserDevice("profile_uuid", $receiver['uuid'])) {
//            return $object->sendCallBackNotification($event_data, $receiver);
//        }
        if (!empty($receiver['device']['device_token'])) {
//            $job = ['event_data' => $event_data, 'receiver' => $receiver, 'user' => $loginUser];
//            dispatch(new \App\Jobs\SendPushNotification($job));
//            $event_data["badge"] = 0;
            $event_data["type"] = 'chat_message';
            $event_data["message"] = 'You have recieved a new message';
            return $object->sendNotificationChat($event_data, $receiver, $loginUser);
//            ($event_data, $receiver);
        }
        return true;
//        return $object->sendChatNotification($event_data, $receiver, $loginUser);
    }

}
