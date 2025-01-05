<?php

namespace App\Http\Controllers;

use App\Message;
use App\User;
use Pusher\Pusher;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Illuminate\Support\Facades\Log;

class CallBackController extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    use \App\Traits\MessageTrait;
    use \App\Traits\PusherTrait;

    private $logger;

    public function __construct(Log $logger) {
        $this->logger = $logger;
//        $this->logger = new Logger(["webhook" => "pusher"]);
//        $this->logger->pushHandler(new StreamHandler(storage_path('logs/call_back.log')), Logger::INFO);
//        parent::__construct();
    }

    /**
     * notify user sent message callback status
     * @param Request $request
     * @return type
     */
    public function notificationCallback(Request $request) {
        try {
            if ($loginUser = $this->validateLoginUser($request)) {
                $inputs = $this->getInputData($request);
                $user = $loginUser['username'] . '=' . $loginUser['id'];
                \Log::info("== calback user ($user) inputs =====");
                \Log::info($inputs);
                $validate = \Validator::make($inputs, ['id' => 'required', 'status' => "required|in:D,S,V", "type" => 'required|in:message,conversation']);
                if ($validate->fails()) {
                    \Log::info($validate->errors()->first());
                    return response()->json(['message' => $validate->errors()->first(), "fv_server" => true], 400);
                }
                if ($loginUser["id"] == 37278) {
                    \DB::enableQueryLog(); // Enable query log
                    $this->logger->info("----call back-----");
                    $this->logger->info($loginUser["username"]);
                    $this->logger->info(print_r($inputs, true));
                }
                /**
                 * If type == message 
                 *   it means only one message status to be updated
                 * else 
                 *   Receiver (Current user) will send the status to Sender 
                 */
                return ($inputs['type'] != 'message') ? $this->conversationReadCallback($inputs, $loginUser) : $this->processMessageCallback($inputs, $loginUser);
            }
            \Log::info("No user found");
            return response()->json((['message' => "No user found", "fv_server" => true]), 400);
        } catch (\Exceotion $ex) {
            $message = $ex->getMessage() . '=>' . $ex->getFile() . ' (' . $ex->getLine() . ' )';
            $this->logger->error("Exception-notificationCallback" . $message);
            return response()->json(['message' => $message, "fv_server" => true], 400);
        }
    }

    public function triggerEvent($message, $receiver) {
        return ['message' => $message, 'receiver' => $receiver];
    }

    /**
     * Process single message read/delivered callback
     * @param type $inputs
     * @return type
     */
    public function processMessageCallback($inputs, $receiver) {
        $username = $receiver['first_name'];
        if ($message = Message::getSingleMessage($inputs['message_uuid'], ['sent'])) {
            \Log::info(print_r($message, true));
            \Log::info("callback extension message payload");
            $message->status = $inputs['status'];
            $sender = !empty($message->customerSender) ? $message->customerSender : $message->freelancerSender;
            $uuid = !empty($message->customerSender) ? $message->customerSender->customer_uuid : $message->freelancerSender->freelancer_uuid;
            $sender['sender_type'] = !empty($message->customerSender) ? "customer" : "freelancer";
            $sender['uuid'] = $uuid;
            $payload['message_from'] = $uuid;
            $payload['message_to'] = $receiver['uuid'];
            $message->save();
            $channel_name = $this->getPresenceChannelName($sender, $receiver, $payload);
            $event_name = 'update-status-' . $uuid;
            $event_data = [
                'receiver_type' => $sender['receiver_type'],
                'sender_type' => $sender['sender_type'],
                'sender' => $sender,
                'receiver' => $receiver,
                'message_uuid' => $message->message_uuid,
                'local_db_key' => $message->local_db_key,
                'status' => $message->status,
                'type' => "notify_callback"
            ];
            \Log::info("===== callback event data => $username ($channel_name) ======");
            \Log::info('==================================');
            \Log::info('testing callback for notification service extension');
            \Log::info('==================================');
            \Log::info($event_data);
            /**
             * This trigger events are application events
             */
            $this->triggerEvent($message, $receiver, 'ms', 1);
            $pusherResponse = $this->triggerPusher($this->getTriggerInfo($channel_name, $event_name, $event_data), $sender->toArray(), $receiver, true);
            \Log::info("user notified successfully(73)");
            return response()->json(['message' => "user notified successfully"], 200);
        }
        \Log::info("No message found(76)");
        return response()->json(['message' => "No message found", "fv_server" => true], 400);
    }

    /**
     * Conversation read callback
     * @param type $inputs
     * @param type $loginUser
     * @return type
     */
    public function conversationReadCallback($inputs, $loginUser) {
        if ($user = User::getIdsByUids([$inputs['id']])) {
            $otherUser = $user[0];
            if ($loginUser["id"] == 37278) {
                $this->logger->info($loginUser['id']);
                $this->logger->info($otherUser);
            }
            if ($loginUser['id'] != $otherUser && Message::isUnreadExists($otherUser, $loginUser['id'], ['D'])) {
                return $this->processConversationCallback($inputs, $loginUser, $otherUser);
            }
            \Log::info("Not viewed conversation not exists");
            return response()->json(['message' => "Not viewed conversation not exists", "fv_server" => true], 400);
        }
        \Log::info("User not found(95)");
        return response()->json(['message' => "User not found", "fv_server" => true], 400);
    }

    /**
     * Process conversation read callback
     * @param type $inputs
     * @param type $loginUser
     * @param type $otherUser
     * @return type
     */
    public function processConversationCallback($inputs, $loginUser, $otherUser) {
        if (Message::updateDeliveredToSeen($loginUser['id'], $otherUser, $inputs['status'], true)) {
            $otherUser = ['uid' => $inputs['id'], 'id' => $otherUser, 'status' => $inputs['status']];
            $this->triggerEvent($otherUser, $loginUser, '', 2);
            $this->sendReadCallback($loginUser, $otherUser);
            \Log::info("user notified successfully");
            if ($loginUser["id"] == 37278) {
//                $this->logger->info(\DB::getQueryLog());
            }
            return response()->json(['message' => "user notified successfully"], 200);
        }
        \Log::info($this->errors['noConversationUpdateFound']);
        return response()->json(['message' => trans("messages.noConversationUpdateFound"), "fv_server" => true], 400);
    }

    /**
     * send read conversation callback via pusher
     * @param type $login_user
     * @param type $other_user
     * @return boolean
     */
    private function sendReadCallback($login_user, $other_user) {
        try {
            $channel_name = $this->getPresenceChannelName($login_user, $other_user);
            $event_name = 'update-status-' . $other_user['uid'];
            $event_data = ['status' => 'V', 'user_id' => $login_user['uid'], 'type' => "conversation_callback"];
            $pusherResponse = $this->triggerPusher($this->getTriggerInfo($channel_name, $event_name, $event_data), $other_user, $login_user, true);
            return true;
        } catch (\Exception $ex) {
            $message = $ex->getMessage() . '=>' . $ex->getFile() . ' (' . $ex->getLine() . ' )';
            $this->logger->error("Exception-sendReadCallback" . $message);
            return false;
        }
    }

    /**
     * Extension call back
     * @param Request $request
     * @return type
     */
    public function extensionCallback(Request $request) {
        try {
            \Log::info("------ Extension callback received ------");
            $inputs = $request->all();
            \Log::info($inputs);

            if (!isset($inputs['auth']) || $inputs['auth'] != ENV('APP_KEY')) {
//            if (!isset($inputs['auth']) || $inputs['auth'] != config('general.callback_security_key')) {
                \Log::info('----- extension callback request authentication failed -------');
                return response()->json(['message' => trans("messages.requestAuthFailed"), "fv_server" => true], 400);
            }
            $validate = \Validator::make($inputs, ['message_uuid' => 'required']);
            if ($validate->fails()) {
                $error = $validate->errors()->first();
                \Log::info("----- extension callback error => $error-------");
                return response()->json(['message' => $error, "fv_server" => true], 400);
            }
            if ($message = Message::getSingleMessageWithReceiver($inputs['message_uuid'], ['sent', 'delivered'])) {
                $inputs['status'] = 'delivered';
                \Log::info("----- extension callback process start -------");
                $receiver = !empty($message['customer_reciever']) ? $message['customer_reciever'] : $message['freelancer_reciever'];
                $receiver['uuid'] = !empty($message['customer_reciever']) ? $message['customer_reciever']['customer_uuid'] : $message['freelancer_reciever']['freelancer_uuid'];
                $receiver['reciever_type'] = !empty($message['customer_reciever']) ? "customer" : "freelancer";
                return $this->processMessageCallback($inputs, $receiver);
            }
            \Log::info('----- extension callback => no user founded ----');
            return response()->json((['message' => trans("messages.noUserFound"), "fv_server" => true]), 400);
        } catch (\Exceotion $ex) {
            $message = $ex->getMessage() . '=>' . $ex->getFile() . ' (' . $ex->getLine() . ' )';
            \Log::info("==== Extension callback exception =====");
            \Log::info($message);
            return response()->json(['message' => $message, "fv_server" => true], 400);
        }
    }

}
