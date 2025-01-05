<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ScreenSetting extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'screen_settings';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'setting_uuid';
    public $timestamps = true;
    protected $fillable = [
        'setting_uuid',
        'freelancer_id',
        'show_option',
        'is_archive'
    ];

    protected function createOrUpdate($column, $value, $data) {

        if (ScreenSetting::where($column, '=', $value)->exists()) {
            return ScreenSetting::where($column, '=', $value)->update($data);
        } else {
            return ScreenSetting::create($data);
        }
    }

    protected function getSetting($column, $value) {
        $result = ScreenSetting::where($column, '=', $value)->first();
        return !empty($result) ? $result->toArray() : [];
    }

}
