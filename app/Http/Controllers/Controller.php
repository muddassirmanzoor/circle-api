<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Freelancer;
use App\Customer;
use App\Helpers\CustomerAppointmentHelper;
use App\Helpers\CommonHelper;

;

class Controller extends BaseController {

    use AuthorizesRequests,
        DispatchesJobs,
        ValidatesRequests;
    use \App\Traits\CommonService;

    /**
     * prepare user input data
     * @param type $inputs
     * @return array
     */
    protected function getInputData($request) {
        \Log::info("Api request receoved inputs ---");
        \Log::info(print_r($request->all(), true));
        $request_inputs = $request->all();
        if ($request->has('data') && !empty($request->has('data'))) {
            $request_inputs = $request->get("data");
        }
        \Log::info("Api request final inputs ---");
        \Log::info(print_r($request_inputs, true));
        return $request_inputs;
    }

    /**
     * Send custom data response
     *
     * @param $status
     * @param $message
     * @param $error_type
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendCustomResponse($message, $status = 400, $error_type = "", $column = "") {
        $resp = ['message' => $message];
        if (!empty($error_type)) {
            if (!empty($column)) {
                $resp[$column] = $error_type;
            } else {
                $resp['error_type'] = $error_type;
            }
        }
        return response()->json(['success' => false, 'message' => $message]);
    }

    /**
     * validate login user
     * @param type $inputs
     * @return boolean
     */
    public function validateLoginUser($inputs) {
        try {
            $code = 400;
//            $token = $request->header('token');
//            $userPayload = $this->getParsedObject($token);

            if ($inputs['login_user_type'] == 'freelancer') {
                $user = Freelancer::checkFreelancer("freelancer_uuid", $inputs['logged_in_uuid']);
                $user['uuid'] = $user['freelancer_uuid'];
            } else {
                $user = Customer::getSingleCustomerDetail("customer_uuid", $inputs['logged_in_uuid']);
                $user['uuid'] = $user['customer_uuid'];
            }
            if ($user) {
                return $user;
            }
            return false;
        } catch (\Exception $ex) {
            \Log::info(print_r($ex->getMessage(), true));
            return response()->json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * get user
     * @param type $inputs
     * @return boolean
     */
    public function getUser($inputs) {
        try {

            if ($inputs['other_user_type'] == 'freelancer') {
                $user = Freelancer::checkFreelancer("freelancer_uuid", $inputs['other_user_id']);
            } else {
                $user = Customer::getSingleCustomerDetail("customer_uuid", $inputs['other_user_id']);
            }
            if ($user) {
                return $user;
            }
            return false;
        } catch (\Exception $ex) {
            \Log::info(print_r($ex->getMessage(), true));
            return response()->json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * get uuids
     * @param type $inputs
     * @return boolean
     */
    public function getSenderRecieverUuids($inputs) {
        try {
            if ($inputs['type'] == 'F') {
                $user = Freelancer::checkFreelancer("freelancer_uuid", $inputs['id']);
            } else {
                $user = Customer::getSingleCustomer("customer_uuid", $inputs['id']);
            }
            if ($user) {
                return $user;
            }
            return false;
        } catch (\Exception $ex) {
            \Log::info(print_r($ex->getMessage(), true));
            return response()->json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * prepare & get presence channel
     * @param type $post
     * @return type
     */
    public function getPresenceChannelName($sender, $receiver, $payload) {
        $sender['id'] = $payload['message_from'];
        $receiver['id'] = $payload['message_to'];
//        if (!empty($sender['freelancer_uuid'])) {
//            $sender['id'] = $sender['freelancer_uuid'];
//            $receiver['id'] = $receiver['customer_uuid'];
//        } else {
//            $sender['id'] = $sender['customer_uuid'];
//            $receiver['id'] = $receiver['freelancer_uuid'];
//        }
//         before this line code commented for admin chat
//        if (!empty($sender['freelancer_uuid'])) {
//            $sender['id'] = $sender['freelancer_uuid'] . '/f';
//            $receiver['id'] = $receiver['customer_uuid'] . '/c';
//        } else {
//            $sender['id'] = $sender['customer_uuid'] . '/c';
//            $receiver['id'] = $receiver['freelancer_uuid'] . '/f';
//        }
        $channel_arr = array($sender['id'], $receiver['id']);
        asort($channel_arr);
        $channel = [$sender['id'], $receiver['id']];
        asort($channel);
        $this->chatChannel = implode("-", $channel);
        return config("general.chat_channel.one_to_one") . implode("-", $channel_arr);
    }

    /**
     * prepare push message event data
     * @param type $post
     * @param type $sender
     * @param type $receiver
     * @return type
     */
    public function prepareMessageEventData($post, $sender, $receiver, $msg_time) {
        $data = [
            'message' => !empty($post['message_content']) ? $post['message_content'] : "",
            'reciever_id' => $receiver['uuid'],
            'reciever_type' => $receiver['type'],
            'status' => "sent",
            'created_on' => $msg_time,
            'sender' => [
                'id' => $sender['uuid'],
                'type' => $sender['type'],
                'name' => $sender['first_name'] . $sender['last_name'],
                'image' => ($sender['type'] == "customer" ? (!empty($sender['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['customer_profile_image'] . $sender['profile_image'] : null) : (!empty($sender['profile_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_profile_image'] . $sender['profile_image'] : null)),
//                'profile_image' => config("image.tawkil_live-cdn-url") . $sender['profile_pic']
            ],
        ];
        if (!empty($post['attachment_type'])) {
            $data['media']['width'] = $data['media']['height'] = $data['media'] = null;
            $data['media'] = [
                'attachment_type' => !empty($post['attachment_type']) ? $post['attachment_type'] : "",
                'attachment' => !empty($post['attachment']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['message_attachments'] . $post['attachment'] : null,
                'video_thumbnail' => null,
            ];
            if ($post['attachment_type'] == "video") {
                $thumb = explode(".", $post['video_thumbnail']);
                $data['media'] = [
                    'attachment_type' => !empty($post['attachment_type']) ? $post['attachment_type'] : "",
                    'attachment' => !empty($post['attachment']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['message_attachments'] . $post['attachment'] : null,
                    'video_thumbnail' => !empty($post['attachment']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['post_video_thumb'] . $thumb[0] . ".jpg" : null,
                ];
                $resolution = getimagesize($data['media']['video_thumbnail']);
                $data['media']['width'] = (float) $resolution[0];
                $data['media']['height'] = (float) $resolution[1];
            }

            if ($post['attachment_type'] == "image") {
                $resolution = getimagesize($data['media']['attachment']);
                $data['media']['width'] = (float) $resolution[0];
                $data['media']['height'] = (float) $resolution[1];
            }
        }
        return $data;
    }

    /**
     * Prepare inbox message response
     * @param type $user
     * @param type $sender
     * @param type $message
     * @return type
     */
    public function prepareMessageResponse($receiver, $sender, $message, $inputs) {
        try {
            if ($receiver['type'] == "freelancer") {
                $obj = new \App\Helpers\FreelancerResponseHelper();
                $images = $obj->freelancerProfileImagesResponse($receiver['profile_image']);
            }
            if ($receiver['type'] == "customer") {
                $obj = new \App\Helpers\CustomerResponseHelper();
                $images = $obj->customerProfileImagesResponse($receiver['profile_image']);
            }
            $userInfo = [
                'uuid' => $receiver['uuid'],
                'type' => $receiver['type'],
                'name' => $receiver['first_name'] . ' ' . $receiver['last_name'],
                'profile_images' => !empty($images) ? $images : null,
            ];
            $messages = $this->prepareMessageContentArr($message->toArray(), $sender, $message->attachments = "", $inputs);
            return ['user' => $userInfo, 'message' => $messages];
        } catch (\Exception $ex) {
            return response()->json(['message' => $ex->getMessage()], 400);
        }
    }

    public function getprofilePicture() {
        
    }

}
