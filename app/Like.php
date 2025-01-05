<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Like extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'likes';
    protected $uuidFieldName = 'like_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['like_uuid', 'user_id', 'post_id', 'liked_by_id', 'is_archive', 'created_at', 'updated_at'];

    /*
     * All model relations goes down here
     *
     */

    public function post() {
        return $this->belongsTo('App\Post', 'post_uuid', 'post_uuid');
    }

    public function customer() {
        return $this->belongsTo('App\Customer', 'liked_by_id', 'user_id');
    }

    protected function addLike($data) {
        return Like::create($data);
    }

    protected function checkAndDeletePostLike($post_id, $liked_by_id) {
        if (Like::where('post_id', '=', $post_id)->where('liked_by_id', '=', $liked_by_id)->exists()) {
            return Like::where('post_id', '=', $post_id)->where('liked_by_id', '=', $liked_by_id)->delete();
        }
        return false;
    }

    protected function checkPostLike($post_id, $liked_by_id) {
        return Like::where('post_id', '=', $post_id)->where('liked_by_id', '=', $liked_by_id)->exists();
    }

    protected function getLikeCount($column, $value) {
        $result = Like::where($column, '=', $value)
                ->where('is_archive', '=', 0)
                ->count();
        return !empty($result) ? $result : 0;
    }

    protected function getLikes($column, $value, $offset = 0, $limit = 20) {
        $result = Like::where($column, '=', $value)->with('customer')
                ->orderBy('created_at', 'DESC')
                ->offset($offset)
                ->limit($limit)
                ->get();
        return !empty($result) ? $result->toArray() : [];
    }
}
