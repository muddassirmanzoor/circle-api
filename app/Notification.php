<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'notifications';
    protected $uuidFieldName = 'notification_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['notification_uuid',
        'sender_id',
        'receiver_id',
        'uuid',
        'message',
        'name',
        'date',
        'purchase_time',
        'class_schedule_uuid',
        'package_uuid',
        'notification_type',
        'is_read',
        'is_archive',
        'created_at',
        'updated_at'
    ];

    /*
     * All model relations goes down here
     *
     */

    public function senderFreelancer() {
        return $this->hasOne('App\Freelancer', 'user_id', 'sender_id')->where('is_archive', '=', 0);
    }

    public function senderCustomer() {
        return $this->hasOne('App\Customer', 'user_id', 'sender_id')->where('is_archive', '=', 0);
    }

    protected function addNotification($data) {
        $result = Notification::create($data);
        return (!empty($result)) ? $result->toArray() : [];
    }

    protected function insertNotification($data) {
        return Notification::insert($data);
    }

    protected function getNotification($column, $value, $offset = null, $limit = null) {
        $query = Notification::where($column, '=', $value)
                ->where('is_archive', '=', 0)
                ->with('senderFreelancer')
                ->with('senderCustomer')
                ->orderBy('created_at', 'desc');

        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $result = $query->get();
        return (!empty($result)) ? $result->toArray() : [];
    }

    protected function checkNotification($inputs = []) {
        $result = Notification::where('sender_id', '=', $inputs['sender_id'])
                ->where('receiver_id', '=', $inputs['receiver_id'])
//                ->where('uuid', '=', $inputs['uuid'])
                ->where('notification_type', '=', $inputs['notification_type'])
                ->where('is_archive', '=', 0)
                ->first();
        return (!empty($result)) ? $result->toArray() : [];
    }

    protected function updateNotification($inputs = [], $data) {
        if (Notification::where('sender_id', '=', $inputs['sender_id'])->where('receiver_id', '=', $inputs['receiver_id'])->where('notification_type', '=', $inputs['notification_type'])->exists()) {
            $result = Notification::where('sender_id', '=', $inputs['sender_id'])->where('receiver_id', '=', $inputs['receiver_id'])->where('notification_type', '=', $inputs['notification_type'])->update($data);
            return (!$result) ? false : true;
        }
        return true;
    }

    protected function updateNotificationStatus($column, $ids = [], $data = []) {
        $result = Notification::whereIn($column, $ids)->update($data);
        return (!$result) ? false : true;
    }

    protected function getNotificationCount($column, $value) {
        $query = Notification::where($column, '=', $value)
                ->where('is_read', '=', 0)
                ->where('is_archive', '=', 0);
        $result = $query->count();
        return $result;
    }

    protected function getNotificationBadgeCount($column, $value, $type = 'all') {

        $query = Notification::where(function ($q) use ($column, $value) {
                    $q->where($column, '=', $value);
                    // send only receiver notification
                    //$q->orWhere('sender_uuid', $value);
                })
                ->where('is_read', '=', 0)
                ->where('is_archive', '=', 0);
        // if ($type == 'appointment') {
        //     $query->where('notification_type', 'new_appointment');
        // }
        // if ($type == 'other') {
        //     $query->where('notification_type', '<>', 'new_appointment');
        // }
        $result = $query->count();
        // dd($result,$column,$value,$type);
        return $result;
    }

    protected function updateNotificationCount($column, $value, $type = 'all') {
        $query = Notification::where(function ($q) use ($column, $value) {
                    $q->where($column, '=', $value);
                    $q->orWhere('sender_id', $value);
                });
        if ($type == 'appointment') {
            $query->where('notification_type', 'new_appointment');
        }
        if ($type == 'other') {
            $query->where('notification_type', '<>', 'new_appointment');
        }
        $result = $query->update(['is_read' => 1]);
        return (!$result) ? false : true;
    }

}
