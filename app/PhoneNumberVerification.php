<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PhoneNumberVerification extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'phone_number_verifications';
    protected $primarykey = 'id';
    protected $fillable = [
        'code_uuid',
        'profile_uuid',
        'phone_number',
        'country_code',
        'country_name',
        'email',
        'verification_code',
        'code_expires_at',
        'status',
        'type',
        'is_archive'
    ];
    protected $uuidFieldName = 'code_uuid';
    protected $guarded = array();

    #-----------------------------User Model----------------------#
    # PhoneNumberVerification , User phone number and code here.  #
    #-------------------------------------------------------------#

    protected function saveConfirmationCode($data = null) {
        $result = PhoneNumberVerification::create($data);
        return !empty($result) ? $result->toArray() : [];
    }

    protected function checkExisting($column = null, $value = null) {
        $result = PhoneNumberVerification::where($column, '=', $value)->first();
        return (!empty($result)) ? $result->toArray() : [];
    }

    protected function deleteRecordById($id) {
        return PhoneNumberVerification::where('id', '=', $id)->delete();
    }

    protected function getConfirmationCode($column, $value, $data = null) {
        $code_data = PhoneNumberVerification::where($column, "=", $value)
                ->where("verification_code", "=", $data['verification_code'])
                ->where("status", "=", 'not_verified')
                ->first();

        return !empty($code_data) ? $code_data->toArray() : [];
    }

    protected function updateConfirmationCode($column, $value, $data = null) {
        return PhoneNumberVerification::where($column, "=", $value)->update($data);
    }

    protected function getTypeBasedCode($email, $type) {
        $result = PhoneNumberVerification::where('email', '=', $email)->where('type', '=', $type)->first();
        return (!empty($result)) ? $result->toArray() : [];
    }

    protected function getTypeBasedCodeByPhone($phn, $type) {
        $result = PhoneNumberVerification::where('phone_number', '=', $phn)->where('type', '=', $type)->first();
        return (!empty($result)) ? $result->toArray() : [];
    }

    protected function deleteRecord($column, $value, $type = null) {
        $query = PhoneNumberVerification::where($column, '=', $value);
        if (!empty($type)) {
            $query = $query->where('type', '=', $type);
        }
        return $query->delete();
    }

}
