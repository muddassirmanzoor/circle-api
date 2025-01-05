<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;
use App\Freelancer;
use App\Customer;

Class ChatHelper {
    /*
      |--------------------------------------------------------------------------
      | ChatHelper that contains all the chat Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use chat processes
      |
     */

    /**
     * Description of ChatHelper
     *
     * @author ILSA Interactive
     */
    public static function validateLoggedInUser($inputs = []) {
        $user = [];
        $response = ['success' => false, 'message' => ChatValidationHelper::prepareSendMessageRules()['message_' . strtolower($inputs['lang'])]['invalid_user_data'], 'data' => $user];
        if (strtolower($inputs['type']) == 'freelancer') {
            $user = Freelancer::checkFreelancer("freelancer_uuid", $inputs['profile_uuid']);
        } elseif (strtolower($inputs['type']) == 'customer') {
            $user = Customer::getSingleCustomer("customer_uuid", $inputs['profile_uuid']);
        }
        if (!empty($user)) {
            $response = ['success' => true, 'message' => 'Valid user', 'data' => $user];
        }
        return $response;
    }

    public static function sendChatMessageProcess($inputs = []) {
        $validation = Validator::make($inputs, ChatValidationHelper::prepareSendMessageRules()['rules'], ChatValidationHelper::prepareSendMessageRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $validate_inputs = $inputs;
        $validate_inputs['profile_uuid'] = $inputs['logged_in_uuid'];
        $validate_inputs['type'] = $inputs['login_user_type'];
        $validate_sender_user = ChatHelper::validateLoggedInUser($validate_inputs);
        if (!$validate_sender_user['success']) {
            return CommonHelper::jsonErrorResponse($validate_sender_user['message']);
        }
        $validate_inputs['profile_uuid'] = $inputs['receiver_id'];
        $validate_inputs['type'] = $inputs['receiver_type'];
        $validate_receiver_user = ChatHelper::validateLoggedInUser($validate_inputs);
        if (!$validate_receiver_user['success']) {
            return CommonHelper::jsonErrorResponse($validate_receiver_user['message']);
        }
        $inputs['sender'] = $validate_sender_user['data'];
        $inputs['receiver'] = $validate_receiver_user['data'];
        return self::sendChatMessage($inputs);
    }

    public static function sendChatMessage($inputs = []) {
        echo '<pre>';
        print_r($inputs);
        exit;
    }
    
     public static function validateSenderAndReceiver($loginUser, $receiver, $inputs) {
        if ($loginUser['id'] == $receiver['id']) {
            return $this->sendCustomResponse("You can't send message to your self", "user", 403);
        } elseif ($inputs['message_to']['type'] == $inputs['message_from']['type']) {
            if ($inputs['message_to']['type'] == 'F') {
                return $this->sendCustomResponse("You can't send message to other freelancers", "", "same_freelancer", 403);
            } elseif ($inputs['message_to']['type'] == 'C') {
                return $this->sendCustomResponse("You can't send message to other customers", "", "same_customer", 403);
            }
        }
        return $this->processMessage($loginUser, $receiver, $inputs);
    }

}

?>
