<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Freelancer;
use App\Customer;
use App\Message;
use App\Helpers\CommonHelper;
use App\Helpers\UuidHelper;
use App\Helpers\ChatHelper;
use App\Helpers\ChatValidationHelper;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ExceptionHelper;
use App\Helpers\MediaUploadHelper;
use App\Helpers\ThumbnailHelper;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    private $request;
    private $sentStatus = 'S';

    //    use \App\Traits\MessageTrait;
    use \App\Traits\PusherTrait;

    use \App\Traits\CommonService;

    private $logger;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Authorize user to pusher
     * @param type $uid
     * @param Request $request
     */
    public function authorizePusher(Request $request)
    {
        try {
            $inputs = $request->all();
            \Log::info("-- authorizePusher starts ---");
            \Log::info(print_r($inputs, true));
            if (!empty($inputs)) {
                $uuid = "";
                $user = [];
                if (strtolower($inputs['login_user_type']) == 'freelancer') {
                    $user = Freelancer::checkFreelancer("freelancer_uuid", $inputs['sender_id']);
                    if (!empty($user)) {
                        $uuid = $user['freelancer_uuid'];
                    }
                } elseif (strtolower($inputs['login_user_type']) == 'customer') {
                    $user = Customer::getSingleCustomer("customer_uuid", $inputs['sender_id']);
                    if (!empty($user)) {
                        $uuid = $user['customer_uuid'];
                    }
                }
                if (empty($inputs['socket_id'])) {
                    $inputs['socket_id'] = null;
                }
                $this->initPusher();
                $resp = $this->pusher->presence_auth(config('general.chat_channel.personal_presence') . $uuid, $inputs['socket_id'], $uuid, $this->preparePusherPresenceData($user, $inputs['login_user_type']));
                echo $resp;
                //                \Log::info("-- authorizePusher end ---");
                //                \Log::info(print_r($resp, true));
                exit;
            }
            header('', true, 403);
            echo ("Forbidden");
            exit;
        } catch (\Exception $ex) {
            $message = $ex->getMessage() . "=>" . $ex->getFile() . "=>" . $ex->getLine();
            \Log::info("-- Exception ---");
            \Log::info(print_r($message, true));
            echo ("Forbidden");
            header('', true, 403);
            exit;
        }
    }

    /**
     *  Authorized in chat window
     * @param type $user_id
     * @param type $receiver_id
     */
    public function authorizeChatWindow(Request $request)
    {
        try {
            $inputs = $request->all();
                     
            Log::channel('pusher')->debug("-- authorize Chat Window inputs  ---");
            Log::channel('pusher')->debug(print_r($inputs, true));
            $user = [];
            if (strtolower($inputs['login_user_type']) == 'freelancer') {
                $user = Freelancer::checkFreelancer("freelancer_uuid", $inputs['sender_id']);
                $uuid = $user['freelancer_uuid'];
                $receiver = Customer::getSingleCustomer("customer_uuid", $inputs['receiver_id']);
                $channel_arr = array($user['freelancer_uuid'], $receiver['customer_uuid']);
            } elseif (strtolower($inputs['login_user_type']) == 'customer') {

                $user = Customer::getSingleCustomer("customer_uuid", $inputs['sender_id']);
                $uuid = $user['customer_uuid'];
                $receiver = Freelancer::checkFreelancer("freelancer_uuid", $inputs['receiver_id']);
                //This condition is added because admin is also a customer so if customer is chating with admin then freelancer will always be empty so we have to check in customer table
                if(empty($receiver))
                {
                    $receiver = Customer::getSingleCustomer("customer_uuid", $inputs['receiver_id']);
                    $channel_arr = array($user['customer_uuid'], $receiver['customer_uuid']);
                }
                else
                {
                    $channel_arr = array($user['customer_uuid'], $receiver['freelancer_uuid']);
                }
            }
            if (!empty($user)) {
                //                \Log::info("-- User data  ---");
                //                \Log::info(print_r($user, true));
                $this->initPusher();
                $presence_data = $this->preparePusherPresenceData($user, strtoupper($inputs['login_user_type']));
                asort($channel_arr);
                $channel_name = config('general.chat_channel.one_to_one') . implode("-", $channel_arr);
                //                \Log::info("-- Channel name  ---");
                //                \Log::info(print_r($channel_name, true));
                $resp = $this->pusher->presence_auth($channel_name, $inputs['socket_id'], $uuid, $presence_data);
                echo $resp;
                \Log::info("-- Response about authorizeChatWindow  ---");
                \Log::info(print_r($resp, true));
                exit;
            }
            //            \Log::info("-- User not found  ---");
            echo ("Forbidden Chat window");
            exit;
        } catch (\Exception $ex) {
            $this->sendExceptionError($ex, $ex->getCode(), 'authorizeChatWindow', 0);
            header('', true, 403);
            echo ("Forbidden Chat window Ex");
            exit;
        }
    }

    /**
     * prepare pusher presence user data array
     * @param type $user
     * @return type
     */
    public function preparePusherPresenceData($user, $type = '')
    {
        \Log::info("-- user info ---");
        \Log::info(print_r($user, true));
        \Log::info("-- type info ---");
        \Log::info(print_r($type, true));
        if ($type == 'freelancer') {
            $response['uuid'] = $user['freelancer_uuid'];
        } elseif ($type == 'customer') {
            $response['uuid'] = $user['customer_uuid'];
        }
        $response['name'] = $user['first_name'] . ' ' . $user['last_name'];
        $response['type'] = $type;
        //        $response['freelancer_uuid'] = $user['freelancer_uuid'];
        //        $response['profile_image'] = $user['profile_image'];
        return $response;
    }

    /**
     * send chat message
     * @param Request $request
     * @return type
     */
    public function sendChatMessage(Request $request)
    {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            //            return ChatHelper::sendChatMessageProcess($inputs);

            $response = ['success' => false, 'message' => ChatValidationHelper::prepareSendMessageRules()['message_' . strtolower($inputs['lang'])]['send_message_error']];

            //            if ($inputs = $this->getInputData($request)) {
            $data = $inputs;
            if ($inputs['sender_type'] == 'freelancer') {
                $inputs['sender_type'] = 'freelancer';
            } elseif ($inputs['sender_type'] == 'customer') {
                $inputs['sender_type'] = 'customer';
            }
            if ($inputs['receiver_type'] == 'freelancer') {
                $inputs['receiver_type'] = 'freelancer';
            } elseif ($inputs['receiver_type'] == 'customer') {
                $inputs['receiver_type'] = 'customer';
            }
            //            $inputs['sender_type'] = strtoupper($inputs['sender_type']);
            //            $inputs['receiver_type'] = strtoupper($inputs['receiver_type']);
            $validation = Validator::make($inputs, ChatValidationHelper::prepareSendMessageRules()['rules']);
            if ($validation->fails()) {
                return CommonHelper::jsonErrorResponse($validation->errors()->first());
            }
            \Log::info("-- sendChatMessage start ---");
            \Log::info(print_r($inputs, true));
            $response = $this->nextStepToSendMessage($inputs, $data);
            //            }
            return $response;
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
            //            $log = [];
            //            $log["method"] = "sendChatMessage";
            //            $log["message"] = $ex->getMessage();
            //            $log["file"] = $ex->getFile();
            //            $log["line"] = $ex->getLine();
            //            \Log::info(print_r($log, true));
            //            return response()->json((['message' => $ex->getMessage()]), 400);
        }
    }

    /**
     * Next step to send message
     * @param type $request
     * @param type $inputs
     * @return type
     */
    private function nextStepToSendMessage($inputs, $data = [])
    {
        $response = $this->sendCustomResponse('User auhorization failed', 401);
        $inputs['message_from']['id'] = $inputs['sender_id'];
        $inputs['message_from']['type'] = $inputs['sender_type'];
        $inputs['message_to']['id'] = $inputs['receiver_id'];
        $inputs['message_to']['type'] = $inputs['receiver_type'];
        //        $sender_data['other_user_type'] = ($data['sender_type'] == "freelancer" ? "freelancer" : "customer");
        $sender_data['login_user_type'] = ($data['login_user_type']);
        $sender_data['logged_in_uuid'] = $data['logged_in_uuid'];
        $loginUser = $this->validateLoginUser($sender_data);
        $receiver_data['other_user_type'] = ($data['receiver_type'] == "freelancer" ? "freelancer" : "customer");
        $receiver_data['other_user_id'] = $inputs['message_to']['id'];
        if ($receiver = $this->getUser($receiver_data)) {
            //            echo "<pre>";
            //            print_r();
            //            exit;
            if ($receiver["is_active"] == false) {
                return response()->json((['success' => false, 'message' => 'User is not available for you.']), 403);
            }
            $response = $this->validateAndSend($loginUser, $receiver, $inputs);
        }
        return $response;
    }

    /**
     * validate and send message
     * @param type $loginUser
     * @param type $receiver
     * @param type $inputs
     * @return type
     */
    private function validateAndSend($loginUser, $receiver, $inputs)
    {
        if ($inputs['sender_id'] == $inputs['receiver_id']) {
            return $this->sendCustomResponse("You can't send message to your self", "user", 403);
        } elseif ($inputs['message_to']['type'] == $inputs['message_from']['type'] && $inputs['chat_with'] == "user") {
            if ($inputs['message_to']['type'] == 'freelancer') {
                return $this->sendCustomResponse("You can't send message to other freelancers", "", "same_freelancer", 403);
            } elseif ($inputs['message_to']['type'] == 'customer') {
                return $this->sendCustomResponse("You can't send message to other customers", "", "same_customer", 403);
            }
        }
        return $this->processMessage($loginUser, $receiver, $inputs);
    }

    /**
     * prepare message and sent
     * @param type $loginUser
     * @param type $receiver
     * @param type $inputs
     * @return type
     */
    private function processMessage($loginUser, $receiver, $inputs)
    {
        $payload = [];
        $payload['message_from'] = ($inputs['sender_type'] == "customer") ? $loginUser['customer_uuid'] : $loginUser['freelancer_uuid'];
        $payload['message_to'] = ($inputs['receiver_type'] == "customer") ? $receiver['customer_uuid'] : $receiver['freelancer_uuid'];
        //        if (!empty($loginUser['freelancer_uuid'])) {
        //            $payload['message_from'] = $loginUser['freelancer_uuid'];
        //            $payload['message_to'] = $receiver['customer_uuid'];
        //        } else {
        //            $payload['message_from'] = $loginUser['customer_uuid'];
        //            $payload['message_to'] = $receiver['freelancer_uuid'];
        //        }
        if (!empty($inputs['attachment'])) {
            if ($inputs['attachment_type'] != "video") {
                MediaUploadHelper::moveSingleS3Image($inputs['attachment'], CommonHelper::$s3_image_paths['message_attachments']);
            }
            if ($inputs['attachment_type'] == "video") {
                MediaUploadHelper::moveSingleS3Image($inputs['attachment'], CommonHelper::$s3_image_paths['message_attachments']);
                if (!empty($inputs['attachment'])) {
                    $result = ThumbnailHelper::processThumbnails($inputs['attachment'], 'message_video');
                    if (!$result['success']) {
                        return CommonHelper::jsonErrorResponse($result['data']['errorMessage']);
                    }
                }
                //                MediaUploadHelper::moveSingleS3Image($inputs['video_thumbnail'], CommonHelper::$s3_image_paths['video_thumbnail']);
                //                $video_thumbnail = CommonHelper::uploadSingleImage($inputs['video_thumbnail'], CommonHelper::$s3_image_paths['message_attachment_thumbnails'], $pre_fix = 'thumbnail_', 's3');
                //                if (!$video_thumbnail['success']) {
                //                    return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['thumbnail_upload_error']);
                //            }
            }
        }
        $payload['message_content'] = isset($inputs['message_content']) && !empty($inputs['message_content']) ? $inputs['message_content'] : "";
        $payload['attachment'] = isset($inputs['attachment']) && !empty($inputs['attachment']) ? $inputs['attachment'] : null;
        $payload['attachment_type'] = isset($inputs['attachment_type']) && !empty($inputs['attachment_type']) ? $inputs['attachment_type'] : null;
        $payload['video_thumbnail'] = isset($inputs['attachment']) && ($inputs['attachment_type'] == "video") ? $inputs['attachment'] : null;
        $payload['local_db_key'] = isset($inputs['local_db_key']) && !empty($inputs['local_db_key']) ? $inputs['local_db_key'] : null;
        return $this->processToSend($payload, $receiver, $loginUser, $inputs);
    }

    /**
     * Process to send chat message
     * @param array $post
     * @param type $receiver
     * @param type $loginUser
     * @param type $inputs
     * @return type
     */
    public function processToSend($post, $receiver, $sender, $inputs)
    {
        $response = [];
        try {
            $response = $this->messageProcessing($post, $receiver, $sender, $inputs);
        } catch (\Illuminate\Database\QueryException $e) {
            //Duplicate local db key (Mysql Unique Key Error)
            if ($e->errorInfo[1] == 1062) {
                $response = $this->handleDuplicateEntryResponse($sender, $receiver, $inputs);
            } else {
                throw new \Exception($e->getMessage());
            }
        }
        return response()->json((["success" => true, "message" => "true", 'data' => $response]), 200);
    }

    /**
     * Message processing
     * @param array $post
     * @param type $receiver
     * @param type $loginUser
     * @param type $inputs
     * @return type
     */
    public function messageProcessing($payload, $receiver, $sender, $inputs)
    {

        $msg_time = $this->getCurrentTime();
        $channel_name = $this->getPresenceChannelName($sender, $receiver, $payload);

        //preparing package message
        $receiver['uuid'] = $payload['message_to'];
        $receiver['type'] = $inputs['receiver_type'];
        $sender['uuid'] = ($inputs['sender_type'] == "customer") ? $sender['customer_uuid'] : $sender['freelancer_uuid'];
        $sender['type'] = $inputs['sender_type'];
        $event_name = config("general.chat_channel.chat_event") . $receiver['uuid'] . $receiver['type'];
        // after this line code commendted for admin chat
        //        if (!empty($receiver['freelancer_uuid'])) {
        //            $receiver['uuid'] = $receiver['freelancer_uuid'];
        //            $receiver['type'] = 'freelancer';
        //            $sender['uuid'] = $sender['customer_uuid'];
        //            $sender['type'] = 'customer';
        //            $event_name = config("general.chat_channel.chat_event") . $receiver['freelancer_uuid'] . $receiver['type'];
        //        } else {
        //            $receiver['uuid'] = $receiver['customer_uuid'];
        //            $receiver['type'] = 'customer';
        //            $sender['uuid'] = $sender['freelancer_uuid'];
        //            $sender['type'] = 'freelancer';
        //            $event_name = config("general.chat_channel.chat_event") . $receiver['customer_uuid'] . $receiver['type'];
        //        }
        $event_data = $this->prepareMessageEventData($payload, $sender, $receiver, $msg_time);
        $dbMessage = $this->prepareModelArr($payload, $sender, $receiver, $inputs);
        //        $dbMessage['created_on'] = $msg_time;
        //        \Log::info("-- chat inputs");
        //        \Log::info(print_r($inputs, true));
        //        $attahments = isset($inputs['media']) ? $this->prepareModelAttachment($event_data, $inputs['media']) : [];
        //        $event_data['media'] = $this->prepareAttachmentUrl($attahments);
        $isSaved = Message::saveMessage($dbMessage, $attahments = "");
        $event_data['id'] = $isSaved->id;
        $event_data['message_uuid'] = $isSaved->message_uuid;
        $event_data['chat_with'] = $isSaved->chat_with;
        $pusherResponse = $this->triggerPusher($this->getTriggerInfo($channel_name, $event_name, $event_data), $receiver, $sender);
        //        $isSaved->status = $this->sentStatus;
        \Log::info("-- Pusher Send Response channel name ---");
        \Log::info(print_r($channel_name, true));
        \Log::info("-- Pusher Send Response  Event Name---");
        \Log::info(print_r($event_name, true));
        \Log::info("-- Pusher Send Response Event Data---");
        \Log::info(print_r($event_data, true));
        \Log::info("-- Pusher Send Response ---");
        \Log::info(print_r($pusherResponse, true));
        $isSaved->save();
        return $this->prepareMessageResponse($receiver, $sender, $isSaved, $inputs);
    }

    /**
     * prepare database saved array
     * @param type $post
     * @param type $sender
     * @param type $receiver
     * @param type $msg_time
     * @param type $inputs
     * @return type
     */
    public function prepareModelArr($post, $sender, $receiver, $inputs)
    {
        $sender_uuid = explode("/", $sender['uuid']);
        $sender_uuid = $sender_uuid[0];
        $receiver_uuid = explode("/", $receiver['uuid']);
        $receiver_uuid = $receiver_uuid[0];
        $thumb = (!empty($post['video_thumbnail'])) ? explode(".", $post['video_thumbnail']) : null;
        $data['thumbnail_key'] = (!empty($thumb)) ? $thumb[0] . ".jpg" : null;
        $sender_type = $inputs['sender_type'];
        $receiver_type = $inputs['receiver_type'];
        // code commented for admin chat
        //        if (!empty($inputs['message_from']['type']) && $inputs['message_from']['type'] == 'freelancer') {
        //            $type = 'freelancer';
        //        } elseif (($inputs['message_from']['type']) && $inputs['message_from']['type'] == 'customer') {
        //            $type = 'customer';
        //        } else {
        //            $type = 'admin';
        //        }
        $event_data = [
            'sender_uuid' => $sender_uuid,
            'receiver_uuid' => $receiver_uuid,
            'message_content' => $post['message_content'],
            'message_uuid' => UuidHelper::generateUniqueUUID("messages", "message_uuid"),
            'status' => 'sent',
            'chat_channel' => $this->chatChannel,
            'local_db_key' => !empty($inputs['local_db_key']) ? (string) $inputs['local_db_key'] : null,
            'sender_type' => $sender_type,
            'receiver_type' => $receiver_type,
            'saved_timezone' => !empty($inputs['saved_timezone']) ? $inputs['saved_timezone'] : "UTC",
            'attachment' => !empty($inputs['attachment']) ? $inputs['attachment'] : null,
            'attachment_type' => !empty($inputs['attachment_type']) ? $inputs['attachment_type'] : null,
            'video_thumbnail' => !empty($data['thumbnail_key']) ? $data['thumbnail_key'] : null,
            //            'deleted_before_seen' => false,
            //            'deleted_after_seen' => false,
            'deleted_one' => 0,
            'deleted_two' => 0,
            'channel' => $this->chatChannel,
            'chat_with' => $inputs['chat_with'],
            //            'receiver_id' => $receiver['id'],
            //            'sender_id' => $sender['id']
        ];
        return $event_data;
    }

    public function sendUndeliveredMessages(Request $request)
    {
        try {
            $user = $this->validateLoginUser($request);
            return $this->sendNotDeliveredMessages($user);
        } catch (\Exception $ex) {
        }
    }

    public static function createAdminChat($profile)
    {
        $save_message = [];
        if (!empty($profile)) {
            $get_admin = Customer::getSupportDetails('type', 'admin');
            if (!empty($get_admin)) {
                $sender_uuid = $get_admin['customer_uuid'];
                $sender_type = "customer";
                $receiver_uuid = ($profile['type'] == "customer") ? $profile['customer_uuid'] : $profile['freelancer_uuid'];
                $receiver_type = $profile['type'];
                $check_admin_msg = Message::checkAdminFirstMsg($get_admin['customer_uuid'], $receiver_uuid);
                if (!empty($check_admin_msg)) {
                    return [];
                }
                $channel_arr = array($sender_uuid, $receiver_uuid);
                asort($channel_arr);
                $channel = [$sender_uuid, $receiver_uuid];
                asort($channel);
                $chatChannel = implode("-", $channel);
                $channel_name = config("general.chat_channel.one_to_one") . implode("-", $channel_arr);
                $event_name = config("general.chat_channel.chat_event") . $receiver_uuid . $receiver_type;
                $msg = "Welcome to Circl! If you ever need us, want to report a bug, leave general feedback or suggest improvements feel free to get in touch and one of the team will get back to you.
Thanks
Circl Team";
                $encoded_msg = base64_encode($msg);
                $data = [
                    'sender_uuid' => $sender_uuid,
                    'receiver_uuid' => $receiver_uuid,
                    'content' => $encoded_msg,
                    'message_uuid' => UuidHelper::generateUniqueUUID("messages", "message_uuid"),
                    'status' => 'sent',
                    'chat_channel' => $chatChannel,
                    'local_db_key' => null,
                    'sender_type' => $sender_type,
                    'receiver_type' => $receiver_type,
                    'saved_timezone' => "UTC",
                    'attachment' => null,
                    'attachment_type' => null,
                    'video_thumbnail' => null,
                    'channel' => $chatChannel,
                    'chat_with' => "admin",
                    'deleted_one' => 0,
                    'deleted_two' => 0,
                ];
                // ----------------- Removed default chat message because we have integrated freshchat
                //$save_message = Message::createMessage($data);
            }
        }
        return ($save_message) ? $save_message : [];
    }

    /**
     * Handle duplicate entry message only
     * @param type $loginUser
     * @param type $receiver
     * @param type $inputs
     * @return type
     */
    public function handleDuplicateEntryResponse($loginUser, $receiver, $inputs)
    {
        $condition = ['message_from' => $loginUser['id'], 'local_db_key' => (int) $inputs['local_db_key']];
        $message = Message::getSingleMessageByCondition($condition);
        $response = $this->prepareMessageResponse($receiver, $loginUser, $message);
        return $response;
    }

    /**
     * Fetch photo refrence from URL
     * @param type $post
     * @return type
     */
    public function filterLocationPostRefUrl($post)
    {
        $reference = "";
        if (!empty($post)) {
            if (!empty($post["bg_image"])) {
                $reference = $post["bg_image"];
            } else if (!empty($post["thumbnail"])) {
                $reference = explode("photoreference", $post["thumbnail"])[1];
            }
        }
        return $reference;
    }
}
