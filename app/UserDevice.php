<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class UserDevice extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'user_devices';
    protected $primarykey = 'id';
    protected $fillable = ['device_uuid', 'user_id', 'device_type', 'device_token', 'version', 'is_archive'];
    protected $uuidFieldName = 'device_uuid';

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = array();
    protected $guarded = array();

    public function user() {
        return $this->belongsTo('\App\User', 'user_id', 'id');
    }

    // register new user
    public static function createDevice($inputs) {
        $publicIP = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'];
        User::where('id', $inputs['user_id'])->update(['last_login_ip' => $publicIP]);
        return UserDevice::create($inputs);
    }

    public static function updateUserDevice($column, $value, $data) {
        return UserDevice::where($column, '=', $value)->update($data);
    }

    public static function getUserDeviceByUser($profile_id, $device_type = null) {
        $result = UserDevice::where('user_id', '=', $profile_id)->where('device_type', '=', $device_type)->first();
        return !empty($result) ? $result->toArray() : [];
    }

    public static function getUserDevice($col, $val) {
        $result = UserDevice::where($col, '=', $val)->where('is_archive', '=', 0)->first();
        return !empty($result) ? $result->toArray() : [];
    }

    public static function getUserAllDevices($col, $val) {
        $result = UserDevice::where($col, '=', $val)->with('user')->where('is_archive', '=', 0)->get();
        return !empty($result) ? $result->toArray() : [];
    }

}
