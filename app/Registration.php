<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'bank_registrations';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'bank_registration_uuid';
    public $timestamps = true;
    protected $fillable = [
        'bank_registration_uuid',
        'profile_uuid',
        'registration_id',
        'profile_type',
        'card_last_digits',
        'expiry_month',
        'expiry_year',
        'card_holder',
        'card_country',
        'is_archive'
    ];

    protected function SaveData($data) {
        $save = Registration::create($data);
        return !empty($save) ? $save->toArray() : [];
    }

    protected function getProfileParticularRegistration($profile_uuid, $registration_id) {
        $result = Registration::where('is_archive', '=', 0)->where('profile_uuid', '=', $profile_uuid)->where('registration_id', '=', $registration_id)->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getRegistrations($cloumn, $value) {
        $result = Registration::where('is_archive', '=', 0)->where($cloumn, '=', $value)->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function checkRegistration($data = []) {
        return Registration::where('is_archive', '=', 0)
                ->where('card_last_digits', '=', $data['card_last_digits'])
                ->where('expiry_month', '=', $data['expiry_month'])
                ->where('expiry_year', '=', $data['expiry_year'])
                ->exists();
    }

}
