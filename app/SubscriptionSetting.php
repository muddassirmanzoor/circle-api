<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SubscriptionSetting extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'subscription_settings';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'subscription_settings_uuid';
    public $timestamps = true;
    protected $fillable = [
        'subscription_settings_uuid',
        'freelancer_id',
        'type',
        'price',
        'currency',
        'is_archive'
    ];

    public function subscriptions() {
        return $this->hasMany('\App\Subscription', 'subscription_settings_uuid', 'subscription_settings_uuid');
    }

    public function freelancer() {
        return $this->hasone('\App\Freelancer', 'id', 'freelancer_id');
    }

    protected function createSubscriptionSetting($data) {
        $result = SubscriptionSetting::create($data);
        return ($result) ? $result : [];
    }

    protected function saveSubscriptionSetting($data) {
        $result = SubscriptionSetting::insert($data);
        return ($result) ? $result : [];
    }

    protected function updateSubscriptionSetting($column, $value, $data) {

        return SubscriptionSetting::where('is_archive', '=', 0)->where($column, '=', $value)->update($data);
    }

    protected function getFreelancerSubscriptions($column, $value) {
        $query = SubscriptionSetting::where('is_archive', '=', 0)->where($column, '=', $value);
        $query->with('freelancer');
        $result = $query->orderBy('id', 'desc')->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getSingleSubscriptionSetting($col, $val) {
        $subscriptions = SubscriptionSetting::where('is_archive', '=', 0)->where($col, '=', $val)->first();
        return !empty($subscriptions) ? $subscriptions->toArray() : [];
    }

    protected function getSubscriptionRevenue($column, $value) {
        $query = SubscriptionSetting::withCount('subscriptions')->where($column, $value)->get();
        return !empty($query) ? $query->toArray() : [];
    }

    protected function checkActiveSubscriptionSetting($column, $value) {
        return SubscriptionSetting::where('is_archive', '=', 0)->where($column, $value)->exists();
    }

    protected function checkSubscriptionSetting($col, $val) {
        $subscriptions = SubscriptionSetting::where($col, '=', $val)->first();
        return !empty($subscriptions) ? $subscriptions->toArray() : [];
    }

}
