<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'notification_settings';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'notification_settings_uuid';
    public $timestamps = true;
    protected $fillable = [
        'notification_settings_uuid',
        'user_id',
        'new_appointment',
        'cancellation',
        'no_show',
        'new_follower',
        'add_to_favourite',
        'is_archive'
    ];

    protected function addSetting($data = []) {
        $save = NotificationSetting::create($data);
        return !empty($save) ? $save->toArray() : [];
    }

    protected function getSettings($col, $val) {
        $data = NotificationSetting::where($col, '=', $val)->first();
        return !empty($data) ? $data->toArray() : [];
    }

    protected function updateSettings($column, $value, $data) {
        return NotificationSetting::where($column, '=', $value)->update($data);
    }

    protected function getSettingsWithType($col, $val, $type = null) {
        $data = NotificationSetting::where($col, '=', $val)->where($type, '=', 1)->first();
        return !empty($data) ? $data->toArray() : [];
    }

}
