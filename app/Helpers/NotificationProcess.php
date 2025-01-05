<?php

namespace App\Helpers;

use Davibennun\LaravelPushNotification\Facades\PushNotification;
/**
 * All methods related to push notification
 * will be here
 */
//use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;
use App\Models\DeviceToken;

class NotificationProcess extends BaseProcess {

    private $selectedUsers = [];

    public function sendChatNotification($data, $receiver, $sender = []) {
        try {
            if (!empty($data) && !empty($receiver['device']) && !empty($sender)) {
                $tokens = $this->prepareTokens([$receiver['device']]);
                $data = $this->prepareSendChatNotiData($data, $sender, $receiver);
                $envoirement = in_array($receiver['id'], $this->selectedUsers) ? 'development' : $receiver['device']['envoirement'];
                $iosResponse = !empty($tokens['ios']) ? $this->sendIOSNotification($tokens, $data, $envoirement) : false;
                $androidResponse = !empty($tokens['android']) ? $this->sendAndroidNotification($tokens, $data) : false;
                return true;
            }
            return true;
        } catch (\Exception $ex) {
            $messages = $ex->getMessage() . '::' . $ex->getFile() . '( ' . $ex->getLine() . ' )';
            \Log::error("===sendChatNotification Exception===");
            \Log::error($messages);
            return false;
        }
    }

    /**
     * Send notification read callback
     * @param type $data
     * @param type $receiver
     * @return boolean
     */
    public function sendCallBackNotification($data, $receiver) {
        if (!empty($data) && !empty($receiver)) {
            $data['message'] = isset($data['message']) ? $data['message'] : "Callback has been sent";
            $data['badge'] = 0;
            $tokens = $this->prepareTokens([$receiver]);
            $iosResponse = !empty($tokens['ios']) ? $this->sendIOSNotification($tokens, $data,$envoirement = 'production') : false;
            $androidResponse = !empty($tokens['android']) ? $this->sendAndroidNotification($tokens, $data) : false;
            return true;
        }
        return true;
    }

    public function prepareSendChatNotiData($data, $sender, $receiver) {
//        $sender['picture'] = $data['sender_picture'];
        $response = [];
        if (!empty($data)) {
            $message = !empty($data['post']) ? $sender['username'] . " shared a post with you" : $sender['username'] . ' sent you a message';
            $response['id'] = (int) $data['id'];
            $response['badge'] = $this->getBadgeCount($receiver['id']);
            $response['type'] = "chat_message";
            $response['message'] = $message;
            $response['content'] = $data['message'];
            $response['status'] = $data['status'];
            $response['sent_at'] = $data['created_at'];
//            $response['sender'] = $this->prepareUser($sender);
            $response['sender'] = $data['sender'];
            $response['is_mute'] = $data['is_mute'];
            $response['post'] = $data['post'];
        }
        return $response;
    }

    /**
     * Get user badge count
     * @param type $user_id
     * @return type
     */
    private function getBadgeCount($user_id) {
        $total = 0;
        if ($counts = $this->getUserActivityCounts($user_id)) {
            foreach ($counts as $key => $value) {
                $total = $total + $value;
            }
        }
        return (int) $total;
    }

    /**
     * get user activity counts
     * @param type $user_id
     * @return type
     */
    public function getUserActivityCounts($user_id) {
        $response = ['like_count' => 0, 'comment_count' => 0, 'follow_count' => 0, 'chat_count' => 0, 'tag_count' => 0];
        $sql = "SELECT COUNT(id) as total,domain,related_to FROM `fayvo_activities` WHERE status='U' AND domain IN('F','R','C','L','M','T') and user_id <> $user_id and related_to=$user_id group BY domain";
        $data = \DB::select($sql);
        if (!empty($data)) {
            foreach ($response as $key => $value) {
                $response[$key] = $this->sumActivityCounts($key, $data);
            }
        }
        $response['chat_count'] = \App\Models\Message::where('message_to', '=', $user_id)->where(function($sql) use($user_id) {
                    $sql->where('delete_one', '<>', $user_id)->where('delete_two', '<>', $user_id);
                })->whereIn('status', ['D', 'S'])->count();
        return $response;
    }

    /**
     * Calculate badge count
     * @param type $key
     * @param type $data
     * @return type
     */
    private function sumActivityCounts($key, $data) {
        $response = 0;
        foreach ($data as $value) {
            $response += $this->countBadge($key, $value);
        }
        return $response;
    }

    /**
     * Count badge
     * @param type $key
     * @param type $value
     * @return type
     */
    private function countBadge($key, $value) {
        $response = 0;
        switch ($key) {
            case 'like_count':
                $response += ($value->domain == 'L') ? ($value->total > 0) ? $value->total : 0 : 0;
                break;
            case 'comment_count':
                $response += ($value->domain == 'C') ? ($value->total > 0) ? $value->total : 0 : 0;
                $response += ($value->domain == 'M') ? ($value->total > 0) ? $value->total : 0 : 0;
                break;
            case 'tag_count':
                $response += ($value->domain == 'T') ? ($value->total > 0) ? $value->total : 0 : 0;
                break;
            case 'follow_count':
                $response += ($value->domain == 'R') ? ($value->total > 0) ? $value->total : 0 : 0;
                $response += ($value->domain == 'F') ? ($value->total > 0) ? $value->total : 0 : 0;
                break;

            default:
                break;
        }
        return $response;
    }

    /**
     * 
     * @param type $notification_data
     * @return array
     */
    public function setIosNotificationDataParameters($notification_data) {
        
        if(empty($notification_data['type'])){
            $notification_data['type'] = 'chat_message';
        }
        $payload = [];
        if ($notification_data['type'] == 'chat_message') {
            $payload = array(
                'aps' => array(
                    'alert' => $notification_data['message'],
//                    'badge' => (int) $notification_data['badge'],
                    'badge' => 0,
                    'sound' => 'default'
                ),
//                'type' => $notification_data['type'],
                'data' => $notification_data,
            );
        }
        elseif ($notification_data['type'] == 'profile_action') {
            $payload = array(
                'aps' => array(
                    'alert' => $notification_data['message'],
//                    'badge' => (int) $notification_data['badge'],
                    'badge' => 0,
                    'sound' => 'default'
                ),
//                'type' => $notification_data['type'],
                'data' => $notification_data['data'],
            );
        } else {
            $payload = [
                'aps' => [
                    'alert' => $notification_data['message'],
//                    'badge' => (int) $notification_data['badge'],
                    'badge' => 0,
                    'sound' => 'default'
                ],
                "data" => [
                    'type' => $notification_data['type'],
                    'order' => $notification_data["order"]
                ]
            ];
        }
        return $payload;
    }

    /**
     * send notification to IOS devices
     * @param type $tokens
     * @param string $data
     * @return boolean
     */
    public function sendIOSNotification($tokens, $notification_data,$envoirement = 'production') {
        try {
            \Log::info("Current envoirement => $envoirement");
            \Log::info("\n");
            $payload = json_encode($this->setIosNotificationDataParameters($notification_data));
            \Log::info("------ IOS push notification payload ---------");
            \Log::info($payload);
            \Log::info("\n");
            \Log::info("-------- Pem File ----------");
            \Log::info(config('push-notification.appNameIOS.' . $envoirement));
            \Log::info("\n");
            \Log::info("-------- Gateway ----------");
            \Log::info(config('push-notification.appNameIOS.ios_push_notification_' . $envoirement));
            // FUNCTION NOTIFICATIONS   
            $ctx = stream_context_create();

            stream_context_set_option($ctx, 'ssl', 'local_cert', config('push-notification.appNameIOS.' . $envoirement));
            stream_context_set_option($ctx, 'ssl', 'passphrase', 'push');
            //send notification 
            $fp = stream_socket_client(
                    config('push-notification.appNameIOS.ios_push_notification_' . $envoirement), $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx
            );
            \Log::info("\n");
            \Log::info("------ appNameIOS FP ---------");
            \Log::info($fp);
            $res = [];
            \Log::info(print_r($tokens['ios'], true));
            foreach ($tokens['ios'] as $deviceToken) {
                $apple_identifier = 'com.ilsa.Tawkil';
                $apple_expiry = time() + (20 * 24 * 60 * 60); // 20 days
                $msg = pack('C', 1) . pack('N', $apple_identifier) . pack('N', $apple_expiry) . pack('n', 32) . pack('H*', str_replace(array(' ', '<', '>'), '', $deviceToken['token'])) . pack('n', strlen($payload)) . $payload;
                $result = fwrite($fp, $msg, strlen($msg));
                $res = json_encode($result);
            }
            fclose($fp);
            \Log::info("=== IOS Notification Sent Successfully ===");
            return true;
        } catch (\Exception $ex) {
            $log = [];
            $log["method"] = "sendIOSNotification";
            $log["message"] = $ex->getMessage();
            $log["file"] = $ex->getFile();
            $log["line"] = $ex->getLine();
            \Log::info(print_r($log, true));
            return false;
        }
    }

    /**
     * send notifications to android devices
     * @param type $tokens
     * @param type $data
     * @return boolean
     */
    public function sendAndroidNotification($tokens, $notification_data) {
        try {
            if (empty($notification_data["order"])) {
                $fields = [
                    'registration_ids' => str_replace(array(' ', '<', '>'), '', [$tokens['android'][0]['token']]),
                    "data" =>
                    $notification_data
                ];
            }if (empty($notification_data["profile_action"])) {
                $fields = [
                    'registration_ids' => str_replace(array(' ', '<', '>'), '', [$tokens['android'][0]['token']]),
                    "data" =>
                    $notification_data['data']
                ];
            } else {
                $fields = [
                    'registration_ids' => str_replace(array(' ', '<', '>'), '', [$tokens['android'][0]['token']]),
                    "data" =>
                    [
                        'type' => $notification_data['type'],
                        'order' => $notification_data["order"],
                        "title" => $notification_data['message']
                    ]
                ];
            }
            $headers = array(
                'Authorization: key=' . config('push-notification.appNameAndroid.apiKey'),
                'Content-Type: application/json'
            );
            \Log::info("=== headers ===");
            \Log::info($headers);
            \Log::info("=== fields ===");
            \Log::info($fields);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            $result = curl_exec($ch);
            \Log::info($result);
            curl_close($ch);
            \Log::info("=== Android Notification Sent Successfully ===");
            return true;
        } catch (\Exception $ex) {
            $messages = $ex->getMessage() . '::' . $ex->getFile() . '( ' . $ex->getLine() . ' )';
            \Log::error($messages);
            return true;
        }
    }

    public function checkAppleErrorResponse($fp) {

        //byte1=always 8, byte2=StatusCode, bytes3,4,5,6=identifier(rowID). Should return nothing if OK.
        $apple_error_response = fread($fp, 6);
        //NOTE: Make sure you set stream_set_blocking($fp, 0) or else fread will pause your script and wait forever when there is no response to be sent.

        if ($apple_error_response) {
            \Log::info("==== Apple Error Found ===");
            //unpack the error response (first byte 'command" should always be 8)
            $error_response = unpack('Ccommand/Cstatus_code/Nidentifier', $apple_error_response);
            if ($error_response['status_code'] == '0') {
                $error_response['status_code'] = '0-No errors encountered';
            } else if ($error_response['status_code'] == '1') {
                $error_response['status_code'] = '1-Processing error';
            } else if ($error_response['status_code'] == '2') {
                $error_response['status_code'] = '2-Missing device token';
            } else if ($error_response['status_code'] == '3') {
                $error_response['status_code'] = '3-Missing topic';
            } else if ($error_response['status_code'] == '4') {
                $error_response['status_code'] = '4-Missing payload';
            } else if ($error_response['status_code'] == '5') {
                $error_response['status_code'] = '5-Invalid token size';
            } else if ($error_response['status_code'] == '6') {
                $error_response['status_code'] = '6-Invalid topic size';
            } else if ($error_response['status_code'] == '7') {
                $error_response['status_code'] = '7-Invalid payload size';
            } else if ($error_response['status_code'] == '8') {
                $error_response['status_code'] = '8-Invalid token';
            } else if ($error_response['status_code'] == '255') {
                $error_response['status_code'] = '255-None (unknown)';
            } else {
                $error_response['status_code'] = $error_response['status_code'] . '-Not listed';
            }
            \Log::info($error_response);
            return true;
        } else {
            \Log::info("==== No Apple Error Found ===");
        }
        return false;
    }

    public function sendIOSNotificationCurl($tokens, $data, $envoirement = 'production') {
        try {
            $payload = json_encode($this->setIosNotificationDataParameters($data));
            $deviceTokens = str_replace(array(' ', '<', '>'), '', $tokens['ios']);
            foreach ($deviceTokens as $device_token) {
                $pem_file = config('push-notification.appNameIOS.certificate_' . $envoirement);
                $pem_secret = 'push';
                $bundleId = 'com.fayvoInternation.fayvo';
                $url = "https://api.development.push.apple.com/3/device/$device_token";
                $apple_expiry = time() + (20 * 24 * 60 * 60); // 20 days
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("apns-topic: $bundleId", "apns-expiration:$apple_expiry"));
                curl_setopt($ch, CURLOPT_SSLCERT, $pem_file);
                curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $pem_secret);
                $response = curl_exec($ch);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                //On successful response you should get true in the response and a status code of 200
                //A list of responses and status codes is available at 
                //https://developer.apple.com/library/ios/documentation/NetworkingInternet/Conceptual/RemoteNotificationsPG/Chapters/TheNotificationPayload.html#//apple_ref/doc/uid/TP40008194-CH107-SW1
                \Log::info("====== Curl execute response ====");
                \Log::info($response);
                \Log::info("===== curl get info response ====");
                \Log::info($response);
            }
            \Log::info("=== Notification sent successfully ====");
            return true;
        } catch (\Exception $ex) {
            $messages = $ex->getMessage() . '::' . $ex->getFile() . '( ' . $ex->getLine() . ' )';
            \Log::error("===Push Notificaion Exception===");
            \Log::error($messages);
            return true;
        }
    }

    /**
     * 
     * @param type $notification_data
     * @return boolean
     */
    public function sendNotification($notification_data) {

        $token = DeviceToken::getDeviceTokenByUser($notification_data["order"]["receiver_id"]);
        if (!empty($token)) {
            \Log::info("-- Reciver token ---");
            \Log::info(print_r($token, true));
            $environment = 'production';
            if ($token['environment'] == 'S') {
                $environment = 'development';
            }
            $tokens = $this->prepareTokens([$token]);
            $iosResponse = !empty($tokens['ios']) ? $this->sendIOSNotification($tokens, $environment, $notification_data) : false;
            $androidResponse = !empty($tokens['android']) ? $this->sendAndroidNotification($tokens, $notification_data) : false;
        } else {
            \Log::info("notification no device found =");
            \Log::info("\n");
            return true;
        }
    }

    /**
     * 
     * @param type $notification_data for chat
     * @return boolean
     */
    public function sendNotificationChat($notification_data, $receiver, $loginUser) {
        if ($token = $receiver["device"]) {
            \Log::info("-- Reciver token ---");
            \Log::info(print_r($token, true));
            $environment = 'production';
            if ($token['environment'] == 'S') {
                $environment = 'development';
            }
            $tokens = $this->prepareTokens([$token]);
//            $notification_data['data'] = $this->prepareChatModel($notification_data, $receiver, $loginUser);
            $iosResponse = !empty($tokens['ios']) ? $this->sendIOSNotification($tokens, $environment, $notification_data) : false;
            $androidResponse = !empty($tokens['android']) ? $this->sendAndroidNotification($tokens, $notification_data) : false;
        } else {
            \Log::info("notification no device found =");
            \Log::info("\n");
            return true;
        }
    }

    public function prepareChatModel($notification_data, $receiver, $loginUser) {
        $notification_data['data']['type'] = $notification_data['type'];
        $notification_data['data']['message_id'] = $notification_data['id'];
        $notification_data['data']['reciever_id'] = $notification_data['reciever_id'];
        $notification_data['data']['name'] = $receiver['name'];
        $notification_data['data']['profile_image'] = config("image.tawkil_live-cdn-url") . $receiver['profile_pic'];
        return $notification_data['data'];
    }

}
