<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use App\Helpers\ExceptionHelper;
use App\Helpers\InboxHelper;
use App\Message;
use DB;

class InboxController extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    use \App\Traits\MessageTrait;
//    use \App\Traits\CommonTrait;
//    public function __construct() {
//        parent::__construct();
//    }

    /**
     * Get user inbox messages
     * @param Request $request
     * @return type+
     */
    public function getInboxMessage(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            $inputs['limit'] = !empty($inputs['limit']) ? $inputs['limit'] : null;
            $inputs['offset'] = !empty($inputs['offset']) ? $inputs['offset'] : null;
            return InboxHelper::getInboxMessage($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            \Log::info("InboxException::" . $ex->getMessage());
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            $message = $ex->getMessage() . "=>" . $ex->getFile() . "(" . $ex->getLine() . ")";
            \Log::info("InboxException::" . $message);
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    /**
     * prepare getInbox service params
     * @param type $request
     * @return type
     */
    public function prepareInboxInputs($request) {
        $data = $request->all();
        \Log::info("-- Inbox inputs --");
        \Log::info(print_r($data, true));
        $inputs['search_key'] = (isset($data['search_key']) && !empty($data['search_key'])) ? $this->trim_string($data['search_key']) : "";
        $inputs['local_timezone'] = (isset($data['local_timezone']) && !empty($data['local_timezone'])) ? $data['local_timezone'] : "";
        $inputs['id'] = isset($data['id']) ? $data['id'] : "";
        $inputs['type'] = isset($data['type']) ? $data['type'] : "";
//        $inputs['not_in'] = (isset($data['not_in']) && !empty($data['not_in'])) ? \App\User::getIdsByids($data['not_in']) : [];
        $inputs['not_in'] = [];
        $inputs['limit'] = !empty($data['limit']) ? $data['limit'] : 500;
        $inputs['offset'] = !empty($data['offset']) ? $data['offset'] : 0;
        return $inputs;
    }

    /**
     * Prepare inbox json response array
     * @param type $messages
     * @param type $loginUser
     * @return type
     */
    private function prepareInboxJsonResponse($messages, $loginUser, $uuid, $inputs) {
        $response = $data = [];
        foreach ($messages["inbox"] as $key => $msg) {
            $exObj = $msg;
            $message = $msg->channelLastMessage;
            if ($message->sender_type == 'freelancer') {
                $receiver = $message->customerReciever->toArray();
                $receiver['uuid'] = $message->customerReciever->customer_uuid;
                $receiver['type'] = 'C';
                $sender = $message->freelancerSender->toArray();
                $sender['uuid'] = $message->freelancerSender->freelancer_uuid;
                $sender['type'] = 'F';
            } else {
                $receiver = $message->freelancerReciever->toArray();
                $receiver['uuid'] = $receiver['freelancer_uuid'];
                $receiver['type'] = 'F';
                $sender = $message->customerSender->toArray();
                $sender['uuid'] = $sender['customer_uuid'];
                $sender['type'] = 'C';
            }

//            if ($loginUser->id == $receiver["id"]) {
//                $receiver = $message->sender->toArray();
//            }
            $response = $this->prepareMessageResponse($receiver, $sender, $message, $inputs);

            $data[$key] = $response;
//            return response()->json((["success" => true, "message" => "true", 'data' => $data]), 200);
            return CommonHelper::jsonSuccessResponse("Successful Request", $data);
        }
        return CommonHelper::jsonSuccessResponse("Successful Request", $data);
    }

    /**
     * get user chat conversation
     * @param Request $request
     * @return type
     */
    public function chatConversation(Request $request) {
        try {
            $inputs = $request->all();
//            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
//            $inputs['limit'] = !empty($inputs['limit']) ? $inputs['limit'] : null;
//            $inputs['offset'] = !empty($inputs['offset']) ? $inputs['offset'] : null;
//            return InboxHelper::getChatConversation($inputs);



            if ($loginUser = $this->validateLoginUser($inputs)) {
//                $inputs = $this->prepareConversationInputs($inputs);
//                if (!$this->validateChatInput($inputs)) {
////                    return response()->json(['message' => $this->errors['otherUserInfoMissing']], 400);
//                    return CommonHelper::jsonErrorResponse($this->errors['otherUserInfoMissing']);
//                }
//                echo '<pre>';
//                print_r($inputs);
//                exit;
//                $inputs['other_user_id'] = (isset($data['other_user_id'])) ? $data['other_user_id'] : "";
//                $inputs['profile_id'] = (isset($data['logged_in_uuid'])) ? $data['logged_in_uuid'] : "";
//                $inputs['type'] = (isset($data['login_user_type'])) ? $data['login_user_type'] : "";
//                $inputs['last_id'] = (isset($data['last_id'])) && !empty($data['last_id']) ? $data['last_id'] : 0;
//                $inputs['local_timezone'] = (isset($data['local_timezone'])) && !empty($data['local_timezone']) ? $data['local_timezone'] : "UTC";
                $inputs['limit'] = !empty($inputs['limit']) ? $inputs['limit'] : 500;
                $inputs['offset'] = !empty($inputs['offset']) ? $inputs['offset'] : 0;
                return $this->getConversation($inputs, $loginUser);
            }
//            return response()->json((['message' => $this->errors['noUserFound']]), 400);
            return CommonHelper::jsonErrorResponse($this->errors['noUserFound']);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
//            return response()->json((['message' => $ex->getMessage()]), 400);
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function prepareConversationInputs($data) {
        \Log::info("--- conversation inputs ---");
        \Log::info(print_r($data, true));
//        $inputs['other_user']['id'] = (isset($data['other_user_id'])) ? $data['other_user_id'] : "";
        $inputs['other_user_id'] = (isset($data['other_user_id'])) ? $data['other_user_id'] : "";
//        $inputs['id'] = (isset($data['id'])) ? $data['id'] : "";
        $inputs['profile_id'] = (isset($data['logged_in_uuid'])) ? $data['logged_in_uuid'] : "";
//        $inputs['type'] = (isset($data['type'])) ? $data['type'] : "";
        $inputs['type'] = (isset($data['login_user_type'])) ? $data['login_user_type'] : "";
//        $inputs['other_user']['type'] = (isset($data['other_user_type'])) ? $data['other_user_type'] : "";
        $inputs['last_id'] = (isset($data['last_id'])) && !empty($data['last_id']) ? $data['last_id'] : 0;
        $inputs['local_timezone'] = (isset($data['local_timezone'])) && !empty($data['local_timezone']) ? $data['local_timezone'] : "UTC";
        $inputs['limit'] = !empty($data['limit']) ? $data['limit'] : 500;
        $inputs['offset'] = !empty($data['offset']) ? $data['offset'] : 0;
//        return $this->prepareLimit($inputs, $data, true);
        return $inputs;
    }

    /**
     * get and prepare chat conversation
     * @param type $inputs
     * @param type $loginUser
     * @return type
     */
    private function getConversation($inputs, $loginUser) {
        if ($inputs['login_user_type'] == 'freelancer') {
            $loginUser['uuid'] = $loginUser['freelancer_uuid'];
            $loginUser['uid'] = $loginUser['freelancer_uuid'];
        } elseif ($inputs['login_user_type'] == 'customer') {
            $loginUser['uuid'] = $loginUser['customer_uuid'];
            $loginUser['uid'] = $loginUser['customer_uuid'];
        }
//        if ($inputs['login_user_type'] == 'freelancer') {
//            $loginUser['uuid'] = $loginUser['freelancer_uuid'] . '/f';
//            $loginUser['uid'] = $loginUser['freelancer_uuid'];
//        } elseif ($inputs['login_user_type'] == 'customer') {
//            $loginUser['uuid'] = $loginUser['customer_uuid'] . '/c';
//            $loginUser['uid'] = $loginUser['customer_uuid'];
//        }
        $data = [];
        $otherUser = $this->getUser($inputs);
        if (!empty($otherUser)) {
            if ($conversation = Message::getChatConversation($inputs, $loginUser['uuid'], $loginUser['uid'], $inputs['limit'], $inputs['offset'])) {
//                $channel = [$inputs["other_user_id"], $loginUser['uuid']];
//                asort($channel);
//                $channel = implode("-", $channel);
//                $delete_data = \App\Message::where('channel', '=', $channel)->delete();
//                if ($delete_data) {
//                    $data = [];
//                }
//                $ids['freelancer_uuid'] = null;
//                $ids['customer_uuid'] = null;
//                if ($inputs['other_user_type'] == "freelancer") {
//                    $ids['freelancer_uuid'] = $inputs['other_user_id'];
//                } elseif ($inputs['login_user_type'] == "freelancer") {
//                    $ids['freelancer_uuid'] = $inputs['profile_id'];
//                }
//                if ($inputs['other_user_type'] == "customer") {
//                    $ids['customer_uuid'] = $inputs['other_user_id'];
//                } elseif ($inputs['login_user_type'] == "customer") {
//                    $ids['customer_uuid'] = $inputs['profile_id'];
//                }
//                $chat_data = \App\Helpers\ClientHelper::getClientChatData($ids);
//                uncomment this line
                $data = $this->prepareConversationArray($conversation, $loginUser, $inputs);
//                $data['has_subscribed'] = $chat_data['has_subscription'];
//                $data['has_appointment'] = $chat_data['has_appointment'];
//                $data['has_followed'] = $chat_data['has_followed'];
//                $data['public_chat'] = ($otherUser['public_chat'] == 1) ? true : false;
            }
//            return response()->json((["success" => true, "message" => "true", 'data' => $data]), 200);
            return CommonHelper::jsonSuccessResponse("Successful Request", $data);
        }
//        return response()->json((['message' => $this->errors['noUserFound']]), 400);
        return CommonHelper::jsonErrorResponse($this->errors['noUserFound']);
    }

    public function getUnreadChatCount(Request $request) {
        $inputs = $request->all();
        return InboxHelper::getUnreadChatCount($inputs['logged_in_uuid']);
    }

    public function UpdateAllChatStatus(Request $request) {
        $inputs = $request->all();
        return InboxHelper::UpdateAllChatStatus($inputs['logged_in_uuid']);
    }

    /**
     * Prepare user conversation
     * @param type $conversation
     * @param type $loginUser
     */
    private function prepareConversationArray($conversation, $loginUser, $inputs) {
        $response = [];
        foreach ($conversation as $key => $value) {
            if (!empty($value['customerSender'])) {
                $sender['uuid'] = $value['customerSender']['customer_uuid'];
                $sender['name'] = $value['customerSender']['first_name'] . ' ' . $value['customerSender']['last_name'];
                $sender['type'] = 'customer';
                if ($value['customerSender']['type'] == "admin") {
                    $sender['profile_images'] = \App\Helpers\CustomerResponseHelper::adminProfileImagesResponse();
                } else {
                    $sender['profile_images'] = \App\Helpers\CustomerResponseHelper::customerProfileImagesResponse($value['customerSender']['profile_image']);
                }
                $response[$key] = $this->prepareMessageContentArr($value, $sender, $value->attachments, $inputs);
            } else {
                $sender['uuid'] = $value['freelancerSender']['freelancer_uuid'];
                $sender['name'] = $value['freelancerSender']['first_name'] . ' ' . $value['freelancerSender']['last_name'];
                $sender['type'] = 'freelancer';
                $sender['profile_images'] = \App\Helpers\FreelancerResponseHelper::freelancerProfileImagesResponse($value['freelancerSender']['profile_image']);
                $response[$key] = $this->prepareMessageContentArr($value, $sender, $value->attachments, $inputs);
            }
        }
        return $response;
    }

    public function updateChatStatus(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return InboxHelper::updateChatStatus($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollback();
            \Log::info("InboxException::" . $ex->getMessage());
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollback();
            $message = $ex->getMessage() . "=>" . $ex->getFile() . "(" . $ex->getLine() . ")";
            \Log::info("InboxException::" . $message);
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

}
