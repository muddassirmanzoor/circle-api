<?php

namespace App\Helpers;

use Carbon;

Class ChatResponseHelper {
    /*
      |--------------------------------------------------------------------------
      | ChatResponseHelper that contains all the chat methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use chat processes
      |
     */

    /**
     * Description of ChatResponseHelper
     *
     * @author ILSA Interactive
     */
//    public static function inboxMessagesResponse($inputs = [], $data = [], $subscribers = [], $subscribed_ids = [], $followings = [], $ids = []) {
    public static function inboxMessagesResponse($inputs = [], $data = [], $related_data = []) {
        $response = [];
        if (!empty($data["inbox"])) {
            foreach ($data["inbox"] as $key => $message) {
                $sender = null;
                $receiver = null;
                $message_data = $message['channel_last_message'];
                $response[$key]['message']['message_uuid'] = $message_data['message_uuid'];
                $response[$key]['message']['id'] = $message_data['id'];
                $response[$key]['message']['content'] = !empty($message_data['content']) ? str_replace("\n", '', $message_data['content']) : "";
                $response[$key]['media'] = null;
                if (!empty($message_data['attachment_type'])) {
                    $response[$key]['media']['attachment'] = !empty($message_data['attachment']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['message_attachments'] . $message_data['attachment'] : null;
                    $response[$key]['media']['video_thumbnail'] = !empty($message_data['video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['video_thumbnail'] . $message_data['video_thumbnail'] : null;
                    $response[$key]['media']['attachment_type'] = !empty($message_data['attachment_type']) ? $message_data['attachment_type'] : null;
                }
                $response[$key]['message']['status'] = $message_data['status'];
                $response[$key]['message']['local_db_key'] = !empty($message_data['local_db_key']) ? $message_data['local_db_key'] : "";
                $response[$key]['message']['sent_at'] = self::getCreatedAtAttribute($message_data['created_at']);
                $response[$key]['message']['chat_with'] = !empty($message_data['chat_with']) ? $message_data['chat_with'] : "user";
//                $response[$key]['message']['unread_count'] = !empty($message['channel_unread_count']) ? $message['channel_unread_count']['total'] : 0;
                if ($message_data['sender_type'] == 'freelancer') {
                    $receiver_data = $message_data['customer_reciever'];
                    $receiver['type'] = 'customer';
                    $receiver['uuid'] = $receiver_data['customer_uuid'];
                    $receiver['name'] = $receiver_data['first_name'] . ' ' . $receiver_data['last_name'];
//                    $receiver['is_following'] = (in_array($receiver_data['customer_uuid'], $related_data['followings'])) ? true : false;
//                    $receiver['has_appointment'] = (in_array($receiver_data['customer_uuid'], $ids['class_freelancer_ids']) || in_array($receiver_data['customer_uuid'], $ids['appointment_freelancer_ids'])) ? true : false;
                    $receiver['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse($receiver_data['profile_image']);
                    $receiver['public_chat'] = ($receiver_data['public_chat'] == 1) ? true : false;
                    $sender_data = $message_data['freelancer_sender'];
                    $sender['type'] = 'freelancer';
                    $sender['uuid'] = $sender_data['freelancer_uuid'];
                    $sender['name'] = $sender_data['first_name'] . ' ' . $sender_data['last_name'];
                    $sender['profile_images'] = FreelancerResponseHelper::freelancerProfileImagesResponse($sender_data['profile_image']);
                    $sender['profession'] = !empty($sender_data['profession']['name']) ? $sender_data['profession']['name'] : "";
                    $sender['public_chat'] = ($sender_data['public_chat'] == 1) ? true : false;
                } elseif ($message_data['sender_type'] == 'customer') {
                    if ($message_data['chat_with'] == "admin") {
                        if (!empty($message_data['freelancer_reciever'])) {
                            $receiver_data = $message_data['freelancer_reciever'];
                            $receiver['type'] = 'freelancer';
                            $receiver['uuid'] = $receiver_data['freelancer_uuid'];
                            $receiver['profession'] = !empty($receiver_data['profession']['name']) ? $receiver_data['profession']['name'] : "";
                            $receiver['name'] = $receiver_data['first_name'] . ' ' . $receiver_data['last_name'];
                            $receiver['profile_images'] = FreelancerResponseHelper::freelancerProfileImagesResponse($receiver_data['profile_image']);
                            $receiver['public_chat'] = ($receiver_data['public_chat'] == 1) ? true : false;
                        } elseif (!empty($message_data['customer_reciever'])) {
                            $receiver_data = $message_data['customer_reciever'];
                            $receiver['type'] = 'customer';
                            $receiver['uuid'] = $receiver_data['customer_uuid'];
                            $receiver['profession'] = !empty($receiver_data['profession']['name']) ? $receiver_data['profession']['name'] : "";
                            $receiver['name'] = $receiver_data['first_name'] . ' ' . $receiver_data['last_name'];
                            $receiver['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse($receiver_data['profile_image']);
                            $receiver['public_chat'] = ($receiver_data['public_chat'] == 1) ? true : false;
                        }
                    } elseif ($message_data['chat_with'] != "admin") {
                        $receiver_data = $message_data['freelancer_reciever'];
                        $receiver['type'] = 'freelancer';
                        $receiver['uuid'] = $receiver_data['freelancer_uuid'];
                        $receiver['profession'] = !empty($receiver_data['profession']['name']) ? $receiver_data['profession']['name'] : "";
                        $receiver['name'] = $receiver_data['first_name'] . ' ' . $receiver_data['last_name'];
                        $receiver['profile_images'] = FreelancerResponseHelper::freelancerProfileImagesResponse($receiver_data['profile_image']);
                        $receiver['public_chat'] = ($receiver_data['public_chat'] == 1) ? true : false;
                    }
                    $sender_data = $message_data['customer_sender'];
                    $sender['type'] = 'customer';
                    $sender['uuid'] = $sender_data['customer_uuid'];
                    $sender['name'] = $sender_data['first_name'] . ' ' . $sender_data['last_name'];
                    $sender['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse($sender_data['profile_image']);
                    $sender['public_chat'] = ($sender_data['public_chat'] == 1) ? true : false;
                }
                $receiver['has_subscribed'] = false;
                $receiver['has_appointment'] = false;
                $receiver['has_followed'] = false;

                $sender_type = ($message_data['sender_type'] == "customer") ? "customer" : "freelancer";
                $receiver_type = ($message_data['sender_type'] != "freelancer") ? "freelancer" : "customer";
                if ($message_data['chat_with'] == "admin") {
                    $receiver['has_subscribed'] = false;
                    $receiver['has_appointment'] = false;
                    $receiver['has_followed'] = false;
                }
                if ($sender_type == "customer" && $receiver_type == "freelancer" && $message_data['chat_with'] != "admin") {
                    $chat_data = ClientHelper::getClientChatData(['customer_id' => $sender_data['id'], 'freelancer_id' => $receiver_data['id']]);
                    $receiver['has_subscribed'] = $chat_data['has_subscription'];
                    $receiver['has_appointment'] = $chat_data['has_appointment'];
                    $receiver['has_followed'] = $chat_data['has_followed'];
                } elseif ($sender_type == "freelancer" && $receiver_type == "customer" && $message_data['chat_with'] != "admin") {
                    $chat_data = ClientHelper::getClientChatData(['customer_id' => $receiver_data['id'], 'freelancer_id' => $sender_data['id']]);
                    $receiver['has_subscribed'] = $chat_data['has_subscription'];
                    $receiver['has_appointment'] = $chat_data['has_appointment'];
                    $receiver['has_followed'] = $chat_data['has_followed'];
                }
//                if ($inputs['login_user_type'] == "customer") {
//                    if ($sender_type == "customer" && $message_data['chat_with'] != "admin") {
//                        $receiver['has_subscribed'] = (in_array($receiver_data['freelancer_uuid'], $related_data['subscribed_ids'])) ? true : false;
//                        $receiver['has_appointment'] = ((in_array($receiver_data['freelancer_uuid'], $related_data['appointment_freelancer_ids'])) ? true : false) || ((in_array($receiver_data['freelancer_uuid'], $related_data['class_freelancer_ids'])) ? true : false);
//                        $receiver['has_followed'] = (in_array($receiver_data['freelancer_uuid'], $related_data['followings'])) ? true : false;
//
//                    } elseif ($sender_type != "ustomer" && $message_data['chat_with'] != "admin") {
//                        $receiver['has_subscribed'] = (in_array($sender_data['freelancer_uuid'], $related_data['subscribed_ids'])) ? true : false;
////                        $receiver['has_appointment'] = (in_array($sender_data['freelancer_uuid'], $related_data['appointment_freelancer_ids'])) ? true : false;
//                        $receiver['has_appointment'] = ((in_array($sender_data['freelancer_uuid'], $related_data['appointment_freelancer_ids'])) ? true : false) || ((in_array($sender_data['freelancer_uuid'], $related_data['class_freelancer_ids'])) ? true : false);
//                        $receiver['has_followed'] = (in_array($sender_data['freelancer_uuid'], $related_data['followings'])) ? true : false;
//                    }
//                } elseif ($inputs['login_user_type'] == "freelancer") {
//                    if ($sender_type == "customer" && $message_data['chat_with'] != "admin") {
//                        $receiver['has_subscribed'] = (in_array($sender_data['customer_uuid'], $related_data['subscribers'])) ? true : false;
//                        $receiver['has_appointment'] = ((in_array($sender_data['customer_uuid'], $related_data['appointment_customer_ids'])) ? true : false) || ((in_array($sender_data['customer_uuid'], $related_data['appointment_customer_ids'])) ? true : false);
//                        $receiver['has_followed'] = (in_array($sender_data['customer_uuid'], $related_data['followers'])) ? true : false;
//
////                        $receiver['has_appointment'] = (in_array($sender_data['customer_uuid'], $related_data['appointment_freelancer_ids'])) ? true : false;
//                    } elseif ($sender_type != "customer" && $message_data['chat_with'] != "admin") {
////                        class_freelancer_ids
//                        $receiver['has_subscribed'] = (in_array($receiver_data['customer_uuid'], $related_data['subscribers'])) ? true : false;
//                        $receiver['has_appointment'] = ((in_array($receiver_data['customer_uuid'], $related_data['appointment_customer_ids'])) ? true : false) || ((in_array($receiver_data['customer_uuid'], $related_data['customer_class_ids'])) ? true : false);
//                        $receiver['has_followed'] = (in_array($receiver_data['customer_uuid'], $related_data['followers'])) ? true : false;
//                    }
//                }
                if ($inputs['logged_in_uuid'] != $sender['uuid']) {
                    $response[$key]['message']['unread_count'] = !empty($message['channel_unread_count']) ? $message['channel_unread_count']['total'] : 0;
                } else {
                    $response[$key]['message']['unread_count'] = 0;
                }
                $response[$key]['message']['sender'] = $sender;
                $response[$key]['message']['receiver'] = $receiver;
            }
            return $response;
        }
        else {
            return $data;
        }
    }

    public static function getCreatedAtAttribute($date) {
        $data = "";
        if (!empty($date)) {
            $data = Carbon\Carbon::parse($date)->format('Y-m-d\TH:i:s.v\Z');
        }
        return $data;
    }

}

?>
