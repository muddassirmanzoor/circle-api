<?php

namespace App\Traits;

/**
 * Description of CommonTrait
 *
 * @author 
 */
trait MessageTrait {

    /**
     * Error messages
     * @var type 
     */
    public $errors = [
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
    ];

    /**
     * Success messages
     * @var type 
     */
    public $success = [
        'generalSuccess' => 'Process successfully completed',
        'messageDelete' => 'Message deleted successfully',
        'chatDeleteSuccess' => 'Chat history deleted successfully',
    ];

}
