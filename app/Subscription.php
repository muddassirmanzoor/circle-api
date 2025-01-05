<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Subscription extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'subscriptions';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'subscription_uuid';
    public $timestamps = true;
    protected $fillable = [
        'subscription_uuid',
        'subscription_settings_id',
        'subscriber_id',
        'subscribed_id',
        'subscription_date',
        'subscription_end_date',
        'transaction_id',
        'card_registration_id',
        'price',
        'payment_status',
        'auto_renew',
        'is_archive'
    ];

    protected function createSubscription($data) {
        $result = Subscription::create($data);
        return !empty($result) ? $result->toArray() : [];
    }

    public function subscription_setting() {
        return $this->hasOne('\App\SubscriptionSetting', 'id', 'subscription_settings_id');
    }

    public function purchase() {
        return $this->belongsTo('\App\Purchases', 'id', 'subscription_id')->where('is_archive', 0);
    }

    public function customer() {
        return $this->hasOne('\App\Customer', 'id', 'subscriber_id');
    }

    protected function getBalance($column, $value, $condition) {

        return Subscription::where($column, '=', $value)
                        ->where('is_archive', '=', 0)
                        ->whereDate('subscription_end_date', '>=', date('Y-m-d H:i:s'))
                        ->with(['subscription_setting'])
                        ->get()->toArray();
    }

    protected function checkSubscription($column, $value) {
        return Subscription::where($column, '=', $value)->where('is_archive', '=', 0)->first();
    }

    protected function getSubscriptionWithSetting($subscriptionId) {
        return Subscription::where('id', '=', $subscriptionId)
                        ->where('is_archive', '=', 0)
                        ->with(['subscription_setting', 'purchase'])
                        ->get()->toArray();
    }

    protected function checkSubscriber($subscriber_id, $subscribed_id) {

        $result = Subscription::where('subscriber_id', '=', $subscriber_id)->where('subscribed_id', '=', $subscribed_id)
                ->where('is_archive', '=', 0)
                ->whereNotIn('payment_status', ['pending_payment', 'failed'])
                ->first();
        return !empty($result) ? true : false;
    }

    protected function checkSubscriberPost($subscriber_uuid, $subscribed_uuid) {
        $result = Subscription::where('subscriber_uuid', '=', $subscriber_uuid)->where('subscribed_uuid', '=', $subscribed_uuid)
                ->where('is_archive', '=', 0)
                ->whereDate('subscription_end_date', '>=', date('Y-m-d'))
                ->first();
        return !empty($result) ? true : false;
    }

    protected function getFreelancerFollowers($column, $value) {
        $result = self::with('GetFollower')->where($column, '=', $value)->where('is_archive', '=', 0)->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getSubscribedIds($column, $value) {
        $result = self::where($column, '=', $value)->where('is_archive', '=', 0)->pluck("subscribed_uuid");
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getSubscribers($column, $value, $limit = null, $offset = null) {
        $query = Subscription::where($column, '=', $value)->where('is_archive', '=', 0)->where('payment_status', 'captured');
        $query = $query->with('customer');
        $query = $query->with('subscription_setting');
        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $query = $query->orderBy('created_at', 'DESC');
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getSubscribersCount($column, $value) {
        return Subscription::where($column, '=', $value)->where('is_archive', 0)->where('payment_status', 'captured')->count();
    }

    public static function getFavouriteProfileIds($column, $value, $pluck_field) {

        $result = Subscription::where($column, '=', $value)->where('is_archive', '=', 0)
                ->whereDate('subscription_end_date', '>=', date('Y-m-d'))
                ->pluck($pluck_field);
        return !empty($result) ? $result->toArray() : [];
    }

    public static function getActiveSubscriptions() {
        $result = Subscription::where('is_archive', '=', 0)->where('auto_renew', 1)->with('subscription_setting')->get();
        return !empty($result) ? $result->toArray() : [];
    }

    public static function getActiveCancelledSubscriptions() {
        $result = Subscription::where('is_archive', '=', 0)->where('auto_renew', 0)->with('subscription_setting')->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function cancelSubscription($col, $val, $data) {
        return Subscription::where($col, '=', $val)->where('is_archive', '=', 0)->update($data);
    }

    public static function subscriptionRenewalAlert() {

        $query = Subscription::whereBetween('subscription_end_date', [Carbon::now(), Carbon::now()->addDay(2)])
                ->with([
            'subscription_setting',
            'purchase.purchasesTransition',
            'purchase.wallet'
        ]);
        return ($query) ? $query->get()->toArray() : null;
    }

    public static function renewalSubscriptionsRecords() {
        Log::channel('recurring_payments')->debug('current Date '. date('Y-m-d'));
        $query = Subscription::whereDate('subscription_end_date', '=', date('Y-m-d'))
                ->with([
            'subscription_setting',
            'purchase.purchasesTransition.customerCards',
            'purchase.wallet',
        ])->where('is_archive',0);
        return ($query) ? $query->get()->toArray() : null;
    }

    public static function checkSubscriptionWithFreelancer($inputs) {
        $inputs['customer_id'] = (isset($inputs['customer_id'])) ? $inputs['customer_id'] : null;
        return Subscription::where('subscriber_id', $inputs['customer_id'])
                        ->where('subscribed_id', $inputs['freelancer_id'])
                        ->where('is_archive', 0)->exists();
    }

    public static function checkSubscriptionTime($inputs) {
        return Subscription::where('subscriber_id', $inputs['customer_id'])
                        ->where('subscribed_id', $inputs['freelancer_id'])
                        ->whereDate('subscription_end_date', '>', date('Y-m-d'))
                        ->where('is_archive', 0)->exists();
    }

    protected function getPassedSubscriptions() {
        $date = date('Y-m-d H:i:s');
        $query = Subscription::where('is_archive', '=', 0);
        $query = $query->where('subscription_end_date', '<', $date);
        $query = $query->whereHas('purchase', function ($q) {
            $q->whereNotIn('status', ['completed']);
        });
        $query = $query->with('subscription_setting');
        $query = $query->with('purchase');

        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

}
