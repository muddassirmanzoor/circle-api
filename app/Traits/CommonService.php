<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Traits;

use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Helpers\CommonHelper;
use App\Helpers\ChatResponseHelper;

/**
 * Description of CommonService
 *
 * @author ILSA Interactive
 */
trait CommonService {

    //return json error response
    public function messageResponse($error = "Error while request execution") {
        // code 
    }

    /**
     * Get current datetime object
     * @return type
     */
    public function getCurrentTime() {
        return Carbon::now()->format("Y-m-d\TH:i:s.v\Z");
    }

    /**
     * Get pusher channler and event to trigger pusher
     * @param type $channel
     * @param type $event
     * @return type
     */
    public function getTriggerInfo($channel, $event, $event_data) {
        return ['channel' => $channel, 'event' => $event, 'data' => $event_data];
    }

    /**
     * Prepare message content
     * @param type $message
     * @param type $sender
     * @return type
     */
    public function prepareMessageContentArr($message, $sender = [], $attachments = [], $inputs = "") {
        if (!empty($inputs['local_timezone'])) {
            $from_time_saved_conversion = CommonHelper::convertDateTimeToTimezone($message['created_at'], $message['saved_timezone'], $inputs['local_timezone']);
        }
        $response = [];
        $time = !empty($inputs['sent_time']) ? $inputs['sent_time'] : $message['created_at'];
//        $response['media']['width'] = null;
//        $response['media']['height'] = null;
        $response['media'] = null;
        $response['content'] = "";
        $response['message_uuid'] = $message['message_uuid'];
        $response['id'] = $message['id'];
        if (!empty($message['content'])) {
            $response['content'] = !empty($message['content']) ? str_replace("\n", '', $message['content']) : "";
        }
        $response['local_db_key'] = !empty($message['local_db_key']) ? $message['local_db_key'] : "";
        $response['chat_with'] = !empty($message['chat_with']) ? $message['chat_with'] : "user";
        $response['status'] = $message['status'];
//        $response['status'] = 'S';
        $response['sent_at'] = ChatResponseHelper::getCreatedAtAttribute($time);
//        $response['sent_at'] = !empty($message->created_at) ? $message->created_at : null;
//        $response['sent_at'] = !empty($from_time_saved_conversion) ? $from_time_saved_conversion : $message['created_at'];
        $response['sender'] = !empty($sender) ? $sender : null;
        $response['message_id'] = $message['id'];
        if (!empty($message['attachment_type'])) {
            $response['media']['attachment'] = !empty($message['attachment']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['message_attachments'] . $message['attachment'] : null;
            $response['media']['video_thumbnail'] = !empty($message['video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['post_video_thumb'] . $message['video_thumbnail'] : null;
            $response['media']['attachment_type'] = !empty($message['attachment_type']) ? $message['attachment_type'] : null;
            if ($response['media']['attachment_type'] == "video") {
//                echo "<pre>";
//                print_r($response['media']['video_thumbnail']);
//                exit;
                $data = getimagesize($response['media']['video_thumbnail']);
                $response['media']['width'] = (float) $data[0];
                $response['media']['height'] = (float) $data[1];
            }
            if ($response['media']['attachment_type'] == "image") {
                $data = getimagesize($response['media']['attachment']);
                $response['media']['width'] = (float) $data[0];
                $response['media']['height'] = (float) $data[1];
            }
        }
//        $medias = $this->prepareMessageAttahments($attachments);
//        if (!empty($medias)) {
//            $response['media'] = $medias;
//        }
        return $response;
    }

    /**
     * Throw exception error
     * @param type $message
     * @param type $status
     * @return type
     */
    public function sendExceptionError($exception, $status = 400, $method = 'undefined', $user_id = 0) {
        try {
            $log = [];
            $log["message"] = $exception->getMessage();
            $log["file"] = $exception->getFile();
            $log["line"] = $exception->getLine();
            \Log::info(print_r($log, true));
            return response()->json(['message' => $exception->getMessage(), "error_type" => 400], 400);
        } catch (\Exception $ex) {
            return response()->json(['message' => $exception->getMessage(), "error_type" => 400], 400);
        }
    }

    /**
     * Validate delete conversation and chat conversation input
     * @param type $inputs
     * @return boolean
     */
    public function validateChatInput($inputs, $messages = []) {
        $validate = \Validator::make($inputs, ['other_user' => 'required'], $messages);
        if ($validate->fails()) {
            return false;
        }
        return true;
    }

}
