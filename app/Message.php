<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
//use App\MessageAttahment;
use Carbon\Carbon;

/**
 * Description of Message
 *
 * @author
 */
class Message extends Model {

//    use \App\Traits\CommonTrait;

    public $table = 'messages';
    public $primarykey = 'id';
    protected $uuidFieldName = 'message_uuid';
    protected $fillable = [
        'id',
        'message_uuid',
        'content',
        "deleted_one",
        'deleted_two',
        'channel',
        'receiver_uuid',
        'sender_uuid',
        'status',
        'sender_type',
        'receiver_type',
        'chat_with',
        'saved_timezone',
        'local_timezone',
        'local_db_key',
        'created_at',
        'updated_at',
        'attachment',
        'attachment_type',
        'video_thumbnail',
    ];

    public function freelancerSender() {
        return $this->belongsTo('App\Freelancer', 'sender_uuid', 'freelancer_uuid')->with('profession');
    }

    public function customerSender() {
        return $this->belongsTo('App\Customer', 'sender_uuid', 'customer_uuid');
    }

    public function customerReciever() {
        return $this->belongsTo('App\Customer', 'receiver_uuid', 'customer_uuid');
    }

    public function freelancerReciever() {
        return $this->belongsTo('App\Freelancer', 'receiver_uuid', 'freelancer_uuid')->with('profession');
    }

    public function channelLastMessage() {
        return $this->hasOne("App\Message", "channel", "channel")->orderBy("id", "desc");
    }

//    public function attachments() {
//        return $this->hasMany('App\MessageAttahment', 'message_id', 'id');
//    }

    public function channelUnreadCount() {
        return $this->hasOne("App\Message", "channel", "channel")
                        ->select(\DB::raw("count(*) as total,channel"))
                        ->where("status", "<>", "viewed")
                        ->groupBy("channel");
    }

    public function totalUnreadCount() {
        return $this->hasOne("App\Message", "id", "id")
                        ->select(\DB::raw("count(*) as total,channel"))
                        ->where("status", "<>", "V")
                        ->groupBy("id");
    }

    public static function saveMessage($inputs, $attachments = []) {
        $inputs['content'] = $inputs['message_content'];
        $message = Message::where('local_db_key', $inputs['local_db_key'])->first();
        if(!$message) {
            $msg = new Message($inputs);
            if ($msg->save()) {
//            if (!empty($attachments)) {
//                foreach ($attachments as $key => $attachment) {
//                    $attachments[$key]['message_id'] = $msg->id;
//                    $attachments[$key]['is_deleted'] = false;
//                    $attachments[$key]['created_on'] = $attachments[$key]['modified_on'] = Carbon::now();
//                }
//                MessageAttahment::insert($attachments);
//            }
                return $msg;
            }
            return false;
        }else{
            return $message;
        }
    }

    public static function createMessage($data) {
        $result = Message::create($data);
        return !empty($result) ? $result->toArray() : [];
    }

    /**
     * Get single message by condition
     * @param type $condition
     * @return type
     */
    public static function getSingleMessageByCondition($condition) {
        $message = Message::where($condition)->first();
        return !empty($message) ? $message : [];
    }

    public static function unreadChatCount($col, $val) {
        $message = Message::where($col, '=', $val)
                ->where("status", "<>", "viewed")
                ->count();
        return !empty($message) ? $message : null;
    }

    /**
     * get inbox messages
     * @param type $user_id
     * @param type $limit
     * @param type $offset
     * @param type $search_key
     * @return type
     */
    public static function getInboxMessages($user_data, $not_channel = [], $limit = null, $offset = null) {
        $not_channel = !empty($not_channel) ? self::prepareChatChannelsArr($user_data, $not_channel) : [];
        $query = Message::select(\DB::raw("count(*) as total,channel"));
        $query = Message::select(DB::raw('*, max(created_at) as created_at'));
        if (!empty($not_channel)) {
            $query->whereNotIn("channel", $not_channel);
        }
        $query = $query->where(function ($sql) use ($user_data) {
                    $sql->where('deleted_one', '<>', $user_data['profile_uuid']);
                    $sql->where('deleted_two', '<>', $user_data['profile_uuid']);
                })->where(function ($sql) use ($user_data) {
                    $sql->where('sender_uuid', '=', $user_data['profile_uuid']);
                    $sql->orWhere('receiver_uuid', '=', $user_data['profile_uuid']);
                })->with(["channelLastMessage" => function ($sql) use ($user_data) {
                        $sql->with('customerReciever');
                        $sql->with('freelancerReciever');
                        $sql->with('customerSender');
                        $sql->with('freelancerSender');
                        $sql->where('deleted_one', '<>', $user_data['profile_uuid']);
                        $sql->where('deleted_two', '<>', $user_data['profile_uuid']);
                    }, "channelUnreadCount" => function ($sql) use ($user_data) {
                        $sql->where('deleted_one', '<>', $user_data['profile_uuid']);
                        $sql->where('deleted_two', '<>', $user_data['profile_uuid']);
                    }])->orderBy('created_at', 'desc')->groupBy("channel");

        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $inbox = $query->get();
        if (!empty($inbox) && !empty($inbox->channelLastMessage) && $inbox->channelLastMessage->sender_type == 'freelancer') {
            $inbox->channelLastMessage->freelancerSender;
            $inbox->channelLastMessage->customerReciever;
        } elseif (!empty($inbox) && !empty($inbox->channelLastMessage)) {
            $inbox->channelLastMessage->customerSender;
            $inbox->channelLastMessage->freelancerReciever;
        }
        $inbox = !empty($inbox) ? $inbox->toArray() : [];
        $count = Message::where(function ($sql) use ($user_data) {
                    $sql->where("sender_uuid", "=", $user_data['profile_uuid']);
                    $sql->where("status", "<>", "viewed");
                })->count();
        return ["inbox" => $inbox, "count" => $count];
//        if (!empty($inbox) && !empty($inbox[0]->channelLastMessage) && $inbox[0]->channelLastMessage->sender_type == 'freelancer') {
//            $inbox[0]->channelLastMessage->freelancerSender;
//            $inbox[0]->channelLastMessage->customerReciever;
//        } elseif (!empty($inbox) && !empty($inbox[0]->channelLastMessage)) {
//            $inbox[0]->channelLastMessage->customerSender;
//            $inbox[0]->channelLastMessage->freelancerReciever;
//        }
//        $inbox = !empty($inbox) ? $inbox->toArray() : [];
//        $count = Message::where(function($sql) use($user_data) {
//                    $sql->where("sender_uuid", "=", $user_data['profile_uuid']);
//                    $sql->where("status", "<>", "viewed");
//                })->count();
//        return ["inbox" => $inbox, "count" => $count];
    }

    /**
     * get conversation
     * @param type $login_user
     * @param type $other_user
     * @param type $offset
     * @param type $limit
     * @return type
     */
    public static function getChatConversation($inputs, $login_user_attached_uuid, $login_user_uuid, $limit = 100, $offset = 0) {
        if (strtolower($inputs["other_user_type"]) == 'freelancer') {
            $other_user_type = 'f';
        } elseif (strtolower($inputs["other_user_type"]) == 'customer') {
            $other_user_type = 'c';
        }
        $channel = [$inputs["other_user_id"], $login_user_attached_uuid];
//        $channel = [$inputs["other_user_id"] . '/' . $other_user_type, $login_user_attached_uuid];
        asort($channel);
        $channel = implode("-", $channel);
//        \DB::enableQueryLog();
        $query = Message::whereIn("channel", [$channel])->where(function ($sql) use ($login_user_attached_uuid, $inputs, $login_user_uuid) {
                            if (isset($inputs['last_id']) && $inputs['last_id'] > 0) {
                                $sql->where('id', '<', $inputs['last_id']);
                            }
                            $sql->where('deleted_one', '<>', $login_user_uuid);
                            $sql->where('deleted_two', '<>', $login_user_uuid);
                        })
                        ->with(['customerSender' => function ($sql) {
                                $sql->select('id', "customer_uuid", 'first_name', 'last_name', 'profile_image', 'type');
                            }]
                        )->orderBy('id', 'DESC');
        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $result = $query->get();
        return $result;
    }

    /**
     * Delete conversation
     * @param type $login_user
     * @param type $other_user
     * @return type
     */
    public static function deleteConversation($login_user, $other_user) {
        $sql = "UPDATE messages
                SET  deleted_two = CASE WHEN deleted_one > 0 THEN '$login_user'  ELSE deleted_two END,
                deleted_one = IF(deleted_one = 0 , '$login_user', deleted_one)
                WHERE ( receiver_uuid = '$login_user' AND sender_uuid = '$other_user' ) OR
                ( sender_uuid = '$login_user' AND receiver_uuid = '$other_user')";
        $result = \DB::statement($sql);
        return $result;
    }

    /**
     * update user conversation read status
     * @param type $login_user
     * @param type $other_user
     * @param type $status
     * @return type
     */
    // Update Msg with message_uuid
    public static function updateStatus($col, $val, $status = 'V', $isDebugMod = false) {
        $query = Message::where($col, '=', $val);
        if ($isDebugMod) {
//            $query->where('status', '<>', 'S');
        }
        return $query->update(['status' => $status]);
    }

    // previous method for updating status
//    public static function updateStatus($login_user, $other_user, $status = 'V', $isDebugMod = false) {
//        $query = Message::where('sender_uuid', '=', $login_user)->where('receiver_uuid', '=', $other_user);
//        if ($isDebugMod) {
////            $query->where('status', '<>', 'S');
//        }
//        return $query->update(['status' => $status]);
//    }

    /**
     * get chat mutual relation
     * @param type $user_id
     * @return type
     */
    static function getMutualRelation($user_id) {
//        $muteChat = ['isMute' => function($sql) use($user_id) {
//                $sql->where('user_id', '=', $user_id);
//            }];
        $unreadCount = ['totalUnreadMessage' => function ($sql) use ($user_id) {

                $sql->where('receiver_id', '=', $user_id);
            }];
//        return array_merge($muteChat, $unreadCount);
        return $unreadCount;
    }

    /**
     * get sigle message by id
     * @param type $message_id
     * @return type
     */
    public static function getSingleMessage($message_id, $status = []) {
        $result = static::where('message_uuid', '=', $message_id)->where(function ($sql)use ($status) {
                    $sql->whereIn('status', $status);
                })
                ->with('customerSender')
                ->with('freelancerSender')
                ->first();
        return !empty($result) ? $result : [];
    }

    public static function checkAdminFirstMsg($admin_uuid, $profile_uuid) {
        $result = Message::where('sender_uuid', '=', $admin_uuid)
                ->where('receiver_uuid', '=', $profile_uuid)
//                ->where(function($q) use($profile_uuid) {
//                    $q->where('sender_uuid', '=', $profile_uuid)
//                    ->ORwhere('receiver_uuid', '=', $profile_uuid);
//                })
                ->first();
        return !empty($result) ? $result->toArray() : [];
    }

    /**
     * Prepare channels array
     * @param type $user_data
     * @param type $channels
     */
    public static function prepareChatChannelsArr($user_data, $channels = []) {
        $response = [];
        foreach ($channels as $key => $value) {
            if (!empty($value)) {
                if ($user_data['profile_uuid'] != $value) {
                    $channel = [$user_data['profile_uuid'], $value];
                    asort($channel);
                    $response[] = implode("-", $channel);
                }
            }
        }
        return $response;
    }

    /**
     * Check unread messages exists
     * @param type $from
     * @param type $to
     * @param type $status
     * @return boolean
     */
    public static function isUnreadExists($from, $to, $status = []) {
        $query = static::where('message_from', '=', $from)->where('message_to', '=', $to)
                ->whereIn('status', $status);
        if ($query->exists()) {
            return true;
        }
        return false;
    }

    /**
     * Is user allowed to delete chat message
     * @param type $id
     * @param type $user_id
     * @return boolean
     */
    public static function isUserMessageDelAllowed($id, $user_id = 0) {
        $query = static::where('id', '=', $id);
        if ($user_id) {
            $query->where(function ($sql) use ($user_id) {
                $sql->where('receiver_uuid', '=', $user_id);
                $sql->orWhere('sender_uuid', '=', $user_id);
            });
            $query->where(function ($sql) use ($user_id) {
                $sql->where('deleted_one', '<>', $user_id);
                $sql->where('deleted_two', '<>', $user_id);
            });
        }

        $result = $query->first();
        return !empty($result) ? $result : [];
    }

    public static function getUndeliverdMessages($message_to, $not_in = []) {
        $result = static::where(function ($sql) use ($message_to, $not_in) {
                    $sql->where('message_to', '=', $message_to);
                    $sql->where('status', '=', 'S');
//                    $sql->where('post_id', '>', 0);
                    $sql->whereNotIn('message_from', $not_in);
                })->where(function ($sql) use ($message_to) {
                    $sql->where('delete_one', '<>', $message_to)->where('delete_two', '<>', $message_to);
                })->with(['sender' => function ($q) use ($message_to) {
                        $q->with(self::getMutualRelation($message_to));
                        $q->select('id', 'uid', 'username', 'full_name', 'picture', 'bucket', 'message_privacy');
                    }])->orderBy('id', 'ASC')->get();
        $grouped = $result->groupBy('message_from');
        return $grouped->toArray();
    }

    /**
     * Check user undelivered messages exists
     * @param type $user_id
     * @return boolean
     */
    public static function checkUndeliveredMsgsExists($user_id) {
        $query = static::where(function ($sql) use ($user_id) {
                    $sql->where('message_to', '=', $user_id);
                    $sql->where('status', '=', 'S');
                    $sql->where('delete_one', '<>', $user_id);
                    $sql->where('delete_two', '<>', $user_id);
                });
        if ($query->exists()) {
            return true;
        }
        return false;
    }

    /**
     * get sigle message by id
     * @param type $message_id
     * @return type
     */
    // previous chat
//    public static function getSingleMessageWithReceiver($message_id, $status = []) {
//        $result = static::where('message_uuid', '=', $message_id)->where(function ($sql)use ($status) {
//                    $sql->whereIn('status', $status);
//                })->whereHas('customerReciever', function ($sql) {
//                    $sql->where("is_archive", "=", 0);
//                })->ORwhereHas('freelancerReceiver', function ($sql) {
//                    $sql->where("is_archive", "=", 0);
//                })->with(['receiver'])->first();
//        return !empty($result) ? $result : [];
//    }
    //for circl chat
    public static function getSingleMessageWithReceiver($message_id, $status = []) {
        $result = static::where('message_uuid', '=', $message_id)->where(function ($sql)use ($status) {
                    $sql->whereIn('status', $status);
                })
                ->with('customerReciever')
                ->with('freelancerReciever')
                ->first();
        return !empty($result) ? $result->toArray() : [];
    }

    /**
     * Get user undelivered messages
     * @param type $user_id
     */
    public static function getUserUndeliveredMessages($user_id) {
        $messages = static::where(function ($sql) use ($user_id) {
                    $sql->where("receiver_uuid", "=", $user_id);
                    $sql->where("status", "=", "sent");
                })->with(["freelancerSender", "customerSender"])->orderBy("id", "ASC")->get();
        return !$messages->isEmpty() ? $messages : [];
    }

    /**
     * Check user undelete conversation exists
     * @param type $user_id
     * @param type $other_user
     * @return boolean
     */
    public static function checkUndeleteConversationExists($user_id, $other_user) {
        $query = static::where(function ($sql) use ($user_id, $other_user) {
                    $sql->where(function ($sql)use ($user_id, $other_user) {
                        $sql->where('sender_uuid', '=', $user_id);
                        $sql->where('receiver_uuid', '=', $other_user);
                    });
                    $sql->orWhere(function ($sql)use ($user_id, $other_user) {
                        $sql->where('receiver_uuid', '=', $user_id);
                        $sql->where('sender_uuid', '=', $other_user);
                    });
                })->where('deleted_one', '<>', $user_id)->where('deleted_two', '<>', $user_id);
        if ($query->exists()) {
            return true;
        }
        return false;
    }

    protected function checkChat($sender_uuid = null, $receiver_uuid = null) {
        $result = Message::where(function ($query) use ($sender_uuid, $receiver_uuid) {
                    $query->where('sender_uuid', '=', $sender_uuid);
                    $query->where('receiver_uuid', '=', $receiver_uuid);
                })
                ->orWhere(function ($q) use ($sender_uuid, $receiver_uuid) {
                    $q->where('sender_uuid', '=', $receiver_uuid);
                    $q->where('receiver_uuid', '=', $sender_uuid);
                })
                ->orderBy('created_at', 'desc')
                ->limit(1)
                ->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function updateMessagesStatus($sender_uuid = null, $receiver_uuid = null, $status = null) {
        $result = Message::where(function ($query) use ($sender_uuid, $receiver_uuid) {
                    $query->where('sender_uuid', '=', $sender_uuid);
                    $query->where('receiver_uuid', '=', $receiver_uuid);
                })
                ->orWhere(function ($q) use ($sender_uuid, $receiver_uuid) {
                    $q->where('sender_uuid', '=', $receiver_uuid);
                    $q->where('receiver_uuid', '=', $sender_uuid);
                })
                ->where('status', '!=', 'viewed')
                ->update($status);
        return true;
    }

    protected function updateAllStatus($status = null) {
        $result = Message::where('status', '!=', 'viewed')
                ->update($status);
        return true;
    }

    protected function pluckFavIds($val) {
        $query = Message::where('sender_uuid', '=', $val)
                ->OrWhere('receiver_uuid', '=', $val)
                ->select('sender_uuid', 'sender_type', 'receiver_uuid', 'receiver_type', 'chat_with')
                ->groupBy('channel');
        $result = $query->get();
        return $result ? $result->toArray() : [];
    }

}
