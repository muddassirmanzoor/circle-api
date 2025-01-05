<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Helpers;

use App\Message;

/**
 * Description of SendUndeliveredMessage
 *
 * @author rizwan
 */
class SendUndeliveredMessage {

    use \App\Traits\PusherTrait;

//    use \App\Traits\CommonTrait;

    private $user_id = 0;
    private $chat_event = NULL;
    private $channel_presence = NULL;
    private $channel_one_to_one = NULL;
    private $pusher = null;
    private $delivered_messages = [];

    public function __construct($user_id, $role) {
        $this->user_id = $user_id;
        $this->chat_event = config('general.chat_channel.chat_event') . $user_id . $role;
        $this->channel_presence = config('general.chat_channel.personal_presence') . $user_id;
        $this->role = $role;
    }

    /**
     * Prepare user once to one channel
     * @param type $from_id
     * @return type
     */
    public function getOneToOneChannel($from_id) {
        $arr = [$this->user_id, $from_id];
        asort($arr);
        $this->channel_one_to_one = config("general.chat_channel.one_to_one") . implode("-", $arr);
        return $this->channel_one_to_one;
    }

    /**
     * Message Update messages status
     * @return boolean
     */
    private function updateMessagesStatus() {
        if (!empty($this->chat_event)) {
            Message::whereIn("id", $this->delivered_messages)->update(["status" => "delivered"]);
        }
        return true;
    }

    public function sendUndeliveredMessages() {
        if ($messages = Message::getUserUndeliveredMessages($this->user_id)) {
            $this->processMesssages($messages);
            $this->updateMessagesStatus();
        } else {
            return ["success" => false, "error" => ["message" => "No undeliverd message founde", "error_type" => ""]];
        }
    }

    private function processMesssages($messages) {
        $this->initPusher();
        foreach ($messages as $key => $message) {
            $res = $this->sendMessage($message->toArray(), $message->attachments);
            if (!$res['channel']) {
                break;
            }
        }
        return true;
    }

    /**
     * Send Single Undelivered message
     * @param type $message
     * @return type
     */
    private function sendMessage($message, $attachments = []) {
        if (!empty($message["customer_sender"]["customer_uuid"])) {
            $sender['uuid'] = $message["customer_sender"]["customer_uuid"];
            $sender['name'] = $message["customer_sender"]["first_name"] . $message["customer_sender"]['last_name'];
            $sender['type'] = 'C';
            $sender['image'] = !empty($message["customer_sender"]['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_image'] . $message["customer_sender"]['profile_image'] : null;
        } else {
            $sender['uuid'] = $message["freelancer_sender"]["freelancer_uuid"];
            $sender['name'] = $message["freelancer_sender"]["first_name"] . $message["freelancer_sender"]['last_name'];
            $sender['type'] = 'F';
            $sender['image'] = !empty($message["freelancer_sender"]['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $message["freelancer_sender"]['profile_image'] : null;
        }
        $oneToOne = $this->getOneToOneChannel($sender['uuid']);
        if ($channel = $this->getChannelNameForBroadCast($oneToOne, ['uuid' => $this->user_id], true)) {
            \Log::info("-- Background Send Chat Message Channel---" . $channel);
            \Log::info("-- Chat Event---");
            \Log::info($this->chat_event);
            $response = $this->pusher->trigger($channel, $this->chat_event, array("data" => $this->prepareChannelData($message, $attachments, $sender)), null, true);
            $this->delivered_messages[] = $message["id"];
            \Log::info("--- Chat Messagae Sent ---");
            return ['channel' => true];
        } else {
            \Log::info("--- User Not founded in channel ----");
            return ['channel' => false];
        }
    }

    /**
     * Prepare channel data
     * @param type $message
     * @return type
     */
    private function prepareChannelData($message, $attachments = [], $sender) {
        $data = [
            'id' => (int) $message['id'],
            'message' => $message['content'],
            'reciever_id' => $this->user_id,
            'status' => "delivered",
            'created_on' => $message['created_at'],
            'sender' => [
                'id' => $sender['uuid'],
                'type' => $sender['type'],
                'name' => $sender['name'],
                'image' => $sender['image'],
//                'profile_image' => config("image.tawkil_live-cdn-url") . $message['sender']['profile_pic']
            ],
        ];
        if (!empty($message['attachment_type'])) {
            $data['media']['width'] = $data['media']['height'] = $data['media'] = null;
            $data['media'] = [
                'attachment_type' => !empty($message['attachment_type']) ? $message['attachment_type'] : "",
                'attachment' => !empty($message['attachment']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['message_attachments'] . $message['attachment'] : null,
                'video_thumbnail' => null,
            ];
            if ($message['attachment_type'] == "video") {
                $thumb = explode(".", $message['video_thumbnail']);
                $data['media'] = [
                    'attachment_type' => !empty($message['attachment_type']) ? $message['attachment_type'] : "",
                    'attachment' => !empty($message['attachment']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['message_attachments'] . $message['attachment'] : null,
                    'video_thumbnail' => !empty($message['attachment']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['post_video_thumb'] . $thumb[0] . ".jpg" : null,
                ];
                $resolution = getimagesize($data['media']['video_thumbnail']);
                $data['media']['width'] = (float) $resolution[0];
                $data['media']['height'] = (float) $resolution[1];
            }

            if ($message['attachment_type'] == "image") {
                $resolution = getimagesize($data['media']['attachment']);
                $data['media']['width'] = (float) $resolution[0];
                $data['media']['height'] = (float) $resolution[1];
            }
        }
//        if ($media = $this->prepareMessageAttahments($attachments)) {
//            $data['media'] = $media;
//        }
        return $data;
    }

}
