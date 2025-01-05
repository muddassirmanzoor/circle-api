<?php

namespace App\Http\Controllers;

use App\Message;
use App\Freelancer;
use App\Customer;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ChatValidationHelper;
use App\Helpers\CommonHelper;

class DeleteChatController extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    use \App\Traits\MessageTrait;
//    use \App\Traits\CommonTrait;
//
//    public function __construct() {
//        parent::__construct();
//    }

    /**
     * delete conversation between two users
     * @param Request $request
     * @return type
     */
    public function deleteConversation(Request $request) {
        try {
            if ($loginUser = $this->validateLoginUser($request)) {
                $username = $loginUser['first_name'] . "=>" . $loginUser['id'];
                \Log::info("==== ($username) ===");
                $this->logInputs = $inputs = $this->getInputData($request);
                \Log::info($inputs);
                $validation = Validator::make($inputs, ChatValidationHelper::prepareDeleteChatRules()['rules']);
                if ($validation->fails()) {
                    return CommonHelper::jsonErrorResponse($validation->errors()->first());
                }

                return $this->coversationDeleteProcess($inputs, $loginUser);
            }
            return response()->json((['message' => $this->errors['noUserFound']]), 400);
        } catch (\Exception $ex) {
            return response()->json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * conversation delete process
     * @param type $inputs
     * @param type $otherUser
     * @return type
     */
    private function coversationDeleteProcess($inputs, $loginUser) {
        $inputs['other_user']['type']=$inputs['other_user_type'];
        $inputs['other_user']['id']=$inputs['other_user_id'];
        $response = response()->json((['message' => $this->errors['noUserFound']]), 400);
        if (strtoupper($inputs['other_user']['type']) == 'F') {
            $uuid = "";
            if ($otherUser = Freelancer::checkFreelancer("freelancer_uuid", $inputs['other_user']['id'])) {
                $uuid = $otherUser['freelancer_uuid'];
            }
        } else {
            if ($otherUser = Customer::getSingleCustomer("customer_uuid", $inputs['other_user']['id'])) {
                $uuid = $otherUser['customer_uuid'];
            }
        }
        if ($otherUser) {
            if (Message::checkUndeleteConversationExists($loginUser['uuid'], $uuid)) {
                if (Message::deleteConversation($loginUser['uuid'], $uuid)) {
                    $response = response()->json((['message' => $this->success['chatDeleteSuccess']]), 200);
                }
            } else {
                $response = response()->json((['message' => $this->errors['noConversationUpdateFound'], 'error_type' => "404"]), 400);
            }
        }

        return $response;
    }

    /**
     * Delete single message
     * @param Request $request
     * @return type
     */
    public function deleteMessage(Request $request) {
        try {
            $inputs = $request->all();
            $loginUser = $this->validateLoginUser($request);
            $username = $loginUser['first_name'] . "=>" . $loginUser['id'];

            $this->logInputs = $inputs = $this->getInputData($request);

            $validate = Validator::make($inputs, ['id' => 'required'], ['id.required' => $this->errors['msg_id_required']]);
            if ($validate->fails()) {
                return response()->json((['message' => $validate->errors()->first()]), 400);
            }
            return $this->processDeleteMessage($inputs, $loginUser);
        } catch (\Exception $ex) {

            return response()->json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * Process to delete message
     * @param type $inputs
     * @param type $loginUser
     * @return type
     */
    public function processDeleteMessage($inputs, $loginUser) {
        if (!empty($loginUser['customer_uuid'])) {
            $loginUser['uuid'] = $loginUser['customer_uuid'];
        } else {
            $loginUser['uuid'] = $loginUser['freelancer_uuid'];
        }
        if ($message = Message::isUserMessageDelAllowed($inputs['message_id'], $loginUser['uuid'])) {
            if ($this->deleteSingleMessage($message, $loginUser)) {
                return response()->json(['message' => $this->success['messageDelete']], 200);
            }

            return response()->json(['message' => $this->errors['generalError']], 400);
        }

        return response()->json(['message' => $this->errors['noMsgDelPermission'], "error_type" => "404"], 404);
    }

    /**
     * Process to validate and delete message
     * @param type $message
     * @param type $loginUser
     * @return boolean
     */
    public function deleteSingleMessage($message, $loginUser) {
        $isOtherDel = ($message->deleted_one > 0 || $message->deleted_two > 0) ? true : false;
        $message = $this->getDeleteColumn($message, $loginUser['uuid']);
        if ($message->deleted_one != $message->deleted_two) {
            if ($message->save()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check available column for delete
     * @param type $message
     * @return string
     */
    public function getDeleteColumn($message, $user_id) {
        if (!empty($message->deleted_one)) {
            $message->deleted_two = $user_id;
        } else {
            $message->deleted_one = $user_id;
        }
        return $message;
    }

}
