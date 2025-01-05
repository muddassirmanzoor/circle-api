<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class Follower extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'followers';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'follow_uuid';
    public $timestamps = true;
    protected $fillable = [
        'follow_uuid',
        'follower_id',
        'following_id'
    ];

    public function customer() {
        return $this->hasOne('\App\Customer', 'id', 'follower_id');
    }

    public function freelancer() {
        return $this->hasOne('App\Freelancer', 'id', 'following_id');
    }

    protected function saveProcessFollower($data) {
        $result = self::create($data);
        return !empty($result) ? $result->toArray() : [];
    }

    protected function checkFollowing($follower_id, $following_id) {
        return self::where('follower_id', '=', $follower_id)->where('following_id', '=', $following_id)->exists();
    }

    protected function deleteFreelancerFollowers($freelancer_id, $customer_id) {
        return self::where('follower_id', '=', $customer_id)->where('following_id', '=', $freelancer_id)->delete();
    }

    protected function getFreelancerFollowers($column, $value, $limit = null, $offset = null) {
        $query = self::where($column, '=', $value);
        $query = $query->with('customer');
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

    protected function getCustomerFollowingStories($column, $value) {
//        $result = self::where($column, '=', $value)->with('freelancer.stories')->get();
        $query = self::where($column, '=', $value)
//                ->with('freelancer');
                ->with('freelancer.ActiveStories.StoryLocation')
                ->whereHas('freelancer', function($query) {
            $query->whereHas('ActiveStories');
//            ->with('ActiveStories');
        });
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getFollowersCount($column, $value) {
        return self::where($column, '=', $value)->count();
    }

    public static function getParticularIds($column, $value, $pluck_field) {
        $result = Follower::where($column, '=', $value)->pluck($pluck_field);
        return !empty($result) ? $result->toArray() : [];
    }

}
