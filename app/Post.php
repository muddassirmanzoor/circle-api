<?php

namespace App;

use App\Helpers\CommonHelper;
use Illuminate\Database\Eloquent\Model;

class Post extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'posts';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'post_uuid';
    public $timestamps = true;
    protected $fillable = [
        'post_uuid',
        'freelance_id',
        'folder_id',
        'caption',
        'text',
        'media_type',
        'post_type',
        'status',
        'url',
        'local_path',
        'is_featured',
        'is_blocked',
        'part_no',
        'is_intro',
        'is_archive',
        'post_action_type'
    ];

    public function freelancer() {
        return $this->belongsTo('App\Freelancer', 'freelance_id', 'id');
    }

    public function media() {
        return $this->hasOne('App\PostMedia', 'post_id', 'id')->where('is_archive', '=', 0);
    }

    public function image() {
        return $this->hasOne('App\PostMedia', 'post_id', 'id')
                        ->where('media_type', 'image')
                        ->where('is_archive', '=', 0);
    }

    public function video() {
        return $this->hasOne('App\PostMedia', 'post_id', 'id')
                        ->where('media_type', 'video')
                        ->where('is_archive', '=', 0);
    }

    public function likes() {
        return $this->hasMany('App\Like', 'post_id', 'id')->where('is_archive', '=', 0);
    }

    public function bookmarks() {
        return $this->hasMany('App\BookMark', 'post_id', 'id');
    }

    public function folders() {
        return $this->hasOne('App\Folder', 'id', 'folder_id');
    }

    public function locations() {
        return $this->hasMany('\App\PostLocation', 'post_id', 'id')->where('is_archive', '=', 0);
    }

    public function content_actions() {
        return $this->hasMany('\App\ContentAction', 'content_id', 'id')->where('content_type', 'post')->where('is_hidden', 1);
    }

    protected function savePost($data) {
        $save = Post::create($data);
        return !empty($save) ? $save->toArray() : [];
    }

    protected function filterPublicProfilePosts($search_params = [], $freelancer_ids = [], $is_search = 0, $limit = null, $offset = null) {
        $result = [];
        $query = null;
        $search_params['user_id'] = CommonHelper::getRecordByUuid('customers', 'customer_uuid', $search_params['customer_uuid'], 'id');

        if (isset($search_params['location_explore']) && $search_params['location_explore'] == 1) {
            if (!empty($search_params['city']) || !empty($search_params['country'])) {
                if (empty($query)) {
                    $query = Post::where('is_archive', '=', 0)->whereHas('locations', function ($q) use ($search_params) {
                        if (!empty($search_params['country'])) {
                            $q->where('country', strtolower($search_params['country']));
                        }
                        if (!empty($search_params['city'])) {
                            $q->where('city', strtolower($search_params['city']));
                        }
                    });
                } else {
                    $query = $query->whereHas('locations', function ($q) use ($search_params) {
                        if (!empty($search_params['country'])) {
                            $q->where('country', strtolower($search_params['country']));
                        }
                        if (!empty($search_params['city'])) {
                            $q->where('city', strtolower($search_params['city']));
                        }
                    });
                }
            }
        } else {
            if (!empty($freelancer_ids) && $is_search == 1) {
                if (empty($query)) {
                    $query = Post::where('is_archive', '=', 0)->whereIn('freelance_id', $freelancer_ids);
                } else {
                    $query = $query->whereIn('freelance_id', $freelancer_ids);
                }
//                if (!empty($search_params['city']) || !empty($search_params['country'])) {
//                    if (empty($query)) {
//                        $query = Post::where('is_archive', '=', 0)->whereHas('locations', function($q) use ($search_params) {
//                            if (!empty($search_params['country'])) {
//                                $q->where('country', strtolower($search_params['country']));
//                            }
//                            if (!empty($search_params['city'])) {
//                                $q->where('city', strtolower($search_params['city']));
//                            }
//                        });
//                    } else {
//                        $query = $query->whereHas('locations', function($q) use ($search_params) {
//                            if (!empty($search_params['country'])) {
//                                $q->where('country', strtolower($search_params['country']));
//                            }
//                            if (!empty($search_params['city'])) {
//                                $q->where('city', strtolower($search_params['city']));
//                            }
//                        });
//                    }
//                }
            }
        }
        if (!empty($query)) {
            $query = $query->where('post_type', '=', 'unpaid')
                    ->where('folder_id', '=', null)
                    ->where('is_blocked', '=', 0)
                    ->where('is_featured', '=', 0);
            $query = $query->with('freelancer.profession');
//            $query = $query->with('image');
//            $query = $query->with('video');
            $query = $query->with('media');
            $query = $query->with('likes');
            $query = $query->with('folders');
            $query = $query->whereDoesntHave('content_actions', function ($q) use ($search_params) {
                $q->where('user_id', $search_params['user_id']);
            });

            if (!empty($offset)) {
                $query = $query->offset($offset);
            }
            if (!empty($limit)) {
                $query = $query->limit($limit);
            }
            $query = $query->orderBy('created_at', 'DESC');
            $result = $query->get();
        }
        return (!empty($result)) ? $result->toArray() : [];
    }

    protected function getGuestProfilePosts($column, $value, $limit = null, $offset = null) {
        $query = Post::where($column, '=', $value)
                ->where('post_type', '=', 'unpaid')
                ->where('folder_uuid', '=', null)
                ->where('is_archive', '=', 0)
                ->where('is_blocked', '=', 0)
                ->where('is_featured', '=', 0);
        $query = $query->with('freelancer.profession');
//        $query = $query->with('image');
//        $query = $query->with('video');
        $query = $query->with('media');
        $query = $query->with('likes');
        $query = $query->with('folders');
        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $query = $query->orderBy('created_at', 'DESC');
        $result = $query->get();
        return (!empty($result)) ? $result->toArray() : [];
    }

    protected function getFolderPosts($column, $value, $limit = 50, $offset = 0) {
        $query = Post::where($column, '=', $value)
                ->where('is_archive', '=', 0)
                ->where('is_blocked', '=', 0)
                ->where('is_featured', '=', 0);

        $query = $query->with('freelancer');

//        $query = $query->with('image');
//        $query = $query->with('video');

        $query = $query->with('media');

        $query = $query->offset($offset);
        $query = $query->limit($limit);
        $query = $query->orderBy('is_intro', 'DESC')->orderBy('created_at', 'DESC');
        $result = $query->get();
        return (!empty($result)) ? $result->toArray() : [];
    }

    protected function getMultiplePosts($column, $ids = [], $query_params = []) {

        $query = Post::whereIn($column, $ids)
                ->where('is_archive', '=', 0)
                ->where('is_blocked', '=', 0);
        if (!empty($query_params['post_type'])) {
            $query = $query->where('post_type', '=', $query_params['post_type']);
        }
        $query = $query->with('freelancer.profession');
//        $query = $query->with('image');
//        $query = $query->with('video');
        $query = $query->with('media');
        $query = $query->with('likes');
        $query = $query->with('folders');
        if (!empty($query_params['offset'])) {
            $query = $query->offset($query_params['offset']);
        }
        if (!empty($query_params['limit'])) {
            $query = $query->limit($query_params['limit']);
        }
        $query = $query->orderBy('created_at', 'DESC');
        $result = $query->get();
        return (!empty($result)) ? $result->toArray() : [];
    }

    protected function getPublicFeedProfilePosts($column, $value, $limit = null, $offset = null, $search_params = null) {
        if (!empty($search_params)) {
            $customerId = isset($search_params['customer_id']) ? $search_params['customer_id'] : $search_params['user_id'];
        }
        $query = Post::where($column, '=', $value)
                ->where('post_type', '=', 'unpaid')
                ->where('folder_id', '=', null)
                ->where('is_archive', '=', 0)
                ->where('is_blocked', '=', 0)
                ->where('is_featured', '=', 0);
        $query = $query->whereHas('freelancer',function($q){
            $q->where('is_archive',0)->where('is_active',1);
        });
        $query = $query->with('freelancer.profession');
//        $query = $query->with('image');
//        $query = $query->with('video');
        $query = $query->with('media');
        $query = $query->with('likes');
        $query = $query->with('folders');
        $query = $query->with('content_actions');
        if (!empty($search_params)) {
            $query = $query->whereDoesntHave('content_actions', function ($q) use ($customerId) {
                $q->where('user_id', $customerId);
            });
        }
        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $query = $query->orderBy('created_at', 'DESC');
        $result = $query->get();
        return (!empty($result)) ? $result->toArray() : [];
    }

    protected function getPostDetail($column, $value) {
        $query = Post::where($column, '=', $value)
                ->where('is_archive', '=', 0);
        $query = $query->with('freelancer.profession');
        $query = $query->with('media');
//        $query = $query->with('image');
//        $query = $query->with('video');
        $query = $query->with('likes');
        $query = $query->with('locations','folders.purchasedPremiumFolder');
        $result = $query->first();
        return (!empty($result)) ? $result->toArray() : [];
    }

    protected function getPostCount($column, $value, $delete_chk = false) {
        return Post::where($column, '=', $value)->where('folder_id', '=', null)->where('post_type', '=', 'unpaid')->when($delete_chk === true, function ($q) {
                    return $q->where('is_archive', 0);
                })->count();
    }

    protected function getSubscriptionPosts($column, $value, $limit = null, $offset = null) {
        $query = Post::where($column, '=', $value)
//                ->where('post_type', '=', 'unpaid')
//                ->where('folder_uuid', '=', null)
                ->where('is_archive', '=', 0)
                ->where('is_blocked', '=', 0)
                ->where('is_featured', '=', 0);

        $query = $query->with('freelancer');
//        $query = $query->with('image');
//        $query = $query->with('video');
        $query = $query->with('media');
        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $query = $query->orderBy('is_intro', 'DESC')->orderBy('created_at', 'desc');
        $result = $query->get();
        return (!empty($result)) ? $result->toArray() : [];
    }

    protected function updatePost($column, $value, $data) {
        return Post::where($column, '=', $value)->update($data);
    }

}
