<?php

namespace App\Helpers;

Class ChatValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | ChatValidationHelper that contains all the chat Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use chat processes
      |
     */

    /**
     * Description of ChatValidationHelper
     *
     * @author ILSA Interactive
     */
    public static function prepareSendMessageRules() {
        $validate['rules'] = [
            'sender_id' => 'required',
            'sender_type' => 'required',
            'receiver_type' => 'required',
            'receiver_id' => 'required',
//            'message_content' => 'required',
            'local_timezone' => 'required',
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
            'sent_time' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getInboxMessagesRules() {
        $validate['rules'] = [
            'profile_uuid' => 'required',
            'local_timezone' => 'required',
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function UpdateChatStatusRules() {
        $validate['rules'] = [
            'other_user_uuid' => 'required',
            'other_user_type' => 'required',
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getChatConversationRules() {
        $validate['rules'] = [
            'other_user_id' => 'required',
            'local_timezone' => 'required',
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function prepareDeleteChatRules() {
        $validate['rules'] = [
            'id' => 'required',
            'type' => 'required',
            'other_user_id' => 'required',
            'logged_in_uuid' => 'required',
            'other_user_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function searchChatUsersRules() {
        $validate['rules'] = [
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function updateChatSettingRules() {
        $validate['rules'] = [
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
            'status' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'generalError' => ' Oops! Something went wrong, Please Try Again',
            "contact_0_required" => "Atleast one phone number is required",
            'noUserFound' => 'No active users found',
            'name_required' => 'Name is required',
            'somethingFix' => 'Something went wrong we will fix it soon.',
            'userRoleID' => 'Please provide user role id',
            'picture' => 'Please provide profile picture',
            'userNotExists' => 'User not exists',
            "message_content_required" => "Message content missing",
            "message_to_required" => "Receiver user information missing",
            "local_db_key_required" => "Chat information missing",
            'userNotAvailable' => 'User is not available for you.',
            'selfSendMessageError' => "You can't send message to your self",
            'AgentToAgentSendMessageError' => "You can't send message to other agents",
            'CustomerToCustomerSendMessageError' => "You can't send message to other customers",
            'otherUserInfoMissing' => 'The other user information is missing ',
            'msg_id_required' => 'Message information missing',
            'noMsgDelPermission' => 'You have no permission to delete this message',
            'other_user_required' => 'The other user information is missing',
            'noConversationUpdateFound' => 'No conversation found to update!',
            'generalSuccess' => 'Process successfully completed',
            'messageDelete' => 'Message deleted successfully',
            'chatDeleteSuccess' => 'Chat history deleted successfully',
            'send_message_error' => 'Sorry! Message could not be sent',
            'invalid_user_data' => 'Invalid data provided for user',
            'logged_in_uuid' => 'login user id is required',
            'login_user_type' => 'login user type is required',
            'status' => 'status is required',
            'other_user_uuid' => 'other user id is required',
            'other_user_type' => 'other user type is required',
            'update_status_error' => 'Status cannot be updated',
        ];
    }

    public static function arabicMessages() {
        return [
            'generalError' => ' Oops! Something went wrong, Please Try Again',
            "contact_0_required" => "Atleast one phone number is required",
            'noUserFound' => 'No active users found',
            'name_required' => 'Name is required',
            'somethingFix' => 'Something went wrong we will fix it soon.',
            'userRoleID' => 'Please provide user role id',
            'picture' => 'Please provide profile picture',
            'userNotExists' => 'User not exists',
            "message_content_required" => "Message content missing",
            "message_to_required" => "Receiver user information missing",
            "local_db_key_required" => "Chat information missing",
            'userNotAvailable' => 'User is not available for you.',
            'selfSendMessageError' => "You can't send message to your self",
            'AgentToAgentSendMessageError' => "You can't send message to other agents",
            'CustomerToCustomerSendMessageError' => "You can't send message to other customers",
            'otherUserInfoMissing' => 'The other user information is missing ',
            'msg_id_required' => 'Message information missing',
            'noMsgDelPermission' => 'You have no permission to delete this message',
            'other_user_required' => 'The other user information is missing',
            'noConversationUpdateFound' => 'No conversation found to update!',
            'generalSuccess' => 'Process successfully completed',
            'messageDelete' => 'Message deleted successfully',
            'chatDeleteSuccess' => 'Chat history deleted successfully',
            'send_message_error' => 'Sorry! Message could not be sent',
            'invalid_user_data' => 'Invalid data provided for user',
            'login_user_type' => 'login user type is required',
            'logged_in_uuid' => 'login user id is required',
            'status' => 'status is required',
        ];
    }

}

?>
