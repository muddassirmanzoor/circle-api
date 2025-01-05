<?php

namespace App;

use App\Helpers\CommonHelper;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use DB;
use PDO;

class Freelancer extends Authenticatable
{

    use Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'freelancers';
    protected $guard = 'freelancer';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
    protected $uuidFieldName = 'freelancer_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'freelancer_uuid',
        'user_id',
        'first_name',
        'last_name',
        'profession_id',
        'profession',
        'company',
        'company_logo',
        'email',
        'phone_number',
        'country_code',
        'country_name',
        'password',
        'profile_image',
        'profile_card_image',
        'cover_image',
        'cover_video',
        'cover_video_thumb',
        'bio',
        'dob',
        'gender',
        'facebook_id',
        'google_id',
        'apple_id',
        'onboard_count',
        'can_travel',
        'travelling_distance',
        'travelling_cost_per_km',
        'default_currency',
        'age',
        'booking_preferences',
        'business_name',
        'business_logo',
        'is_login',
        'profile_type',
        'has_subscription',
        'has_bank_detail',
        'public_chat',
        'receive_subscription_request',
        'is_archive',
        'created_at',
        'updated_at'
    ];
    protected $hidden = [
        'remember_token',
    ];

    public function primaryLocation()
    {
        return $this->hasOne('\App\FreelancerLocation', 'freelancer_id', 'id')->where('type', '=', 'primary')->where('is_archive', '=', 0);
    }

    public function secondaryLocation()
    {
        return $this->hasOne('\App\FreelancerLocation', 'freelancer_id', 'id')->where('type', '=', 'secondary')->where('is_archive', '=', 0);
    }

    public function posts()
    {
        return $this->hasMany('\App\Post', 'freelance_id', 'id');
    }

    public function SinglePost()
    {
        return $this->hasOne('\App\Post', 'freelance_id', 'id')->where('is_archive', 0);
    }

    public function locations()
    {
        return $this->hasMany('\App\FreelancerLocation', 'freelancer_id', 'id')->where('is_archive', '=', 0);
    }

    public function primary_location()
    {
        return $this->hasOne('\App\FreelancerLocation', 'freelancer_id', 'id')->where('is_archive', '=', 0);
    }

    public function SocialMedia()
    {
        return $this->hasMany('\App\SocialMedia', 'user_id', 'user_id');
    }

    public function SavedCategory()
    {
        return $this->hasOne('\App\SavedCategory', 'user_id', 'user_id')->orderBy('created_at', 'desc');
    }

    public function BankDetail()
    {
        return $this->hasOne('\App\BankDetail', 'freelancer_id', 'id')->where('is_archived', 0);
    }

    public function following()
    {
        return $this->hasMany('App\Follower', 'following_id', 'id');
    }

    public function favourites()
    {
        return $this->hasMany('App\Favourite', 'freelancer_id', 'id');
    }

    public function subscriptions()
    {
        return $this->hasMany('App\Subscription', 'subscribed_id', 'id');
    }

    public function subscriptionFreelancer()
    {
        return $this->hasMany('App\Subscription', 'freelancer_id', 'id');
    }

    public function SubscriptionSettings()
    {
        return $this->hasMany('\App\SubscriptionSetting', 'freelancer_id', 'id')->where('is_archive', '=', 0);
    }

    public function reviews()
    {
        return $this->hasMany('\App\Review', 'reviewed_id', 'id')->where('is_review', '=', 1);
    }

    public function latest_reviews()
    {
        return $this->hasMany('\App\Review', 'reviewed_id', 'id')->orderBy('created_at', 'DESC')->limit(1);
    }

    public function likes()
    {
        return $this->hasMany('\App\Like', 'user_id', 'id');
    }

    public function ActiveStories()
    {
        return $this->hasMany('\App\Story', 'freelancer_id', 'id')
            ->whereBetween('created_at', [now()->subDays(30), now()])
            ->where('is_active', '=', 1)
            ->orderBy('created_at', 'desc');
    }

    public function qualifications()
    {
        return $this->hasMany('\App\Qualification', 'freelancer_id', 'id');
    }

    public function FreelancerCategories()
    {
        return $this->hasMany('\App\FreelanceCategory', 'freelancer_id', 'id')->where('is_archive', '=', 0);
    }

    public function profession()
    {

        return $this->hasOne('\App\Profession', 'id', 'profession_id');
    }

    protected function saveFreelancer($data)
    {
        $result = Freelancer::create($data);
        return !empty($result) ? $result->toArray() : [];
    }

    protected function updateFreelancer($column, $value, $data)
    {
        return Freelancer::where($column, '=', $value)->update($data);
    }

    protected function pluckFreelancerAttribute($column, $value, $pluck_column = 'id')
    {
        $result = Freelancer::where($column, '=', $value)->pluck($pluck_column);
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getDetail($where)
    {
        $result = Freelancer::where($where)->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getFreelancerDetail($column, $value)
    {

        $result = Freelancer::where($column, '=', $value)
            ->where('is_archive', 0)
            ->with('SocialMedia')
            ->with('qualifications')
            ->with('FreelancerCategories.category', 'SavedCategory.category')
            ->with('locations.location')
            ->with('SubscriptionSettings')
            ->with('profession')
            ->with(['reviews' => function ($q) {
                $q->with('customer', 'appointment');
                $q->whereDoesntHave('reply');
            }])
            ->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getRecommendedProfile($column, $value)
    {
        $result = Freelancer::where($column, '=', $value)
            ->where(function ($q) {
                $q->where(function ($nq) {
                    $nq->where('profile_type', '>', 0)
                        ->where('profile_type', '!=', 2);
                })
                    ->orWhere(function ($nq) {
                        $nq->where('profile_type', 2)
                            ->where('receive_subscription_request', 1)
                            ->where('has_subscription_content', 1);
                    });
            })
            ->with('qualifications')
            ->with('locations.location')
            ->with('reviews')
            ->with('profession')
            ->orderBy('created_at', 'desc')
            ->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getSuggestedProfiles($column, $value, $offset = 0, $limit = 10)
    {
        $result = Freelancer::where($column, '=', $value)
            ->where(function ($q) {
                $q->where(function ($nq) {
                    $nq->where('profile_type', '>', 0)
                        ->where('profile_type', '!=', 2);
                })
                    ->orWhere(function ($nq) {
                        $nq->where('profile_type', 2)
                            ->where('receive_subscription_request', 1)
                            ->where('has_subscription_content', 1);
                    });
            })
            ->with('qualifications')
            ->with('locations.location')
            ->with('profession')
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getSubscribedProfiles($column, $value, $offset = 0, $limit = 10)
    {
        $result = Freelancer::where($column, '=', $value)
            ->where('has_bank_detail', '=', 1)
            ->where('has_subscription_content', 1)
            ->where('receive_subscription_request', 1)
            ->whereHas('SubscriptionSettings')
            ->with('SubscriptionSettings')
            ->with('profession')
            // ->whereHas('SinglePost', function ($q) {
            //     $q->where('folder_id', '!=', null);
            // })
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getLatestReviewsProfiles($column, $value, $offset = 0, $limit = 10)
    {
        $result = Freelancer::where($column, '=', $value)
            ->whereHas('latest_reviews')
            ->with("profession")
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();
        foreach ($result as $freelancer){
            $latestReview = $freelancer->latest_reviews()
                ->select('id', 'reviewed_id', 'rating as average_rating', 'created_at', 'review')
                ->where('is_review', 1)
                ->where('is_archive', 0)
                ->orderBy('created_at', 'desc')
                ->first();
            if ($latestReview) {
                $freelancer->latest_reviews = [
                    [
                        'average_rating' => $latestReview->average_rating,
                        'reviewed_id' => $latestReview->reviewed_id,
                        'created_at' => $latestReview->created_at->toIso8601String(),
                        'review' => $latestReview->review,
                    ]
                ];
            } else {
                $freelancer->latest_reviews = [];
            }
        }
        return !empty($result) ? $result->toArray() : [];
    }

    protected function checkFreelancer($col, $val)
    {
        $result = Freelancer::where($col, $val)->where('is_archive', '=', 0)->with('BankDetail')->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function checkFreelancerWithDetails($col, $val)
    {
        $result = Freelancer::where($col, $val)->where('is_archive', '=', 0)->with('BankDetail')->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function searchFreelancers($search_params = [], $limit = null, $offset = null)
    {

        //        DB::enableQueryLog();
        $query = Freelancer::where('freelancers.is_archive', '=', 0);
        if (isset($search_params['search_text']) && !empty($search_params['search_text'])) {
            // $query->where(function ($text_qry) use ($search_params) {
            //     $text_qry->orWhere('first_name', 'LIKE', '%' . trim($search_params['search_text']) . '%');

            //     $text_qry->orWhere('last_name', 'LIKE', '%' . trim($search_params['search_text']) . '%');

            //     $text_qry->orWhere(DB::raw('concat(first_name," ",last_name)'), 'LIKE', '%' . trim($search_params['search_text']) . '%');

            //     $text_qry->orWhere('business_name', 'LIKE', '%' . trim($search_params['search_text']) . '%');

            //     $text_qry->orWhereHas('profession', function ($query_p) use ($search_params) {
            //         $query_p->where('name', 'LIKE', '%' . $search_params['search_text'] . '%');
            //     });

            //     $text_qry->orWhereHas('FreelancerCategories', function ($query_c) use ($search_params) {
            //         $query_c->where('name', 'LIKE', '%' . $search_params['search_text'] . '%');
            //     });
            // });

            $query->where(function ($text_qry) use ($search_params) {
                $text_qry->where('first_name', 'LIKE', '%' . trim($search_params['search_text']) . '%')
                    ->orWhere('last_name', 'LIKE', '%' . trim($search_params['search_text']) . '%')
                    ->orWhere('business_name', 'LIKE', '%' . trim($search_params['search_text']) . '%');
            });
        }


        if (isset($search_params['profession_id']) && !empty($search_params['profession_id'])) {
            $query = $query->whereHas('profession', function ($q) use ($search_params) {
                $q->where('profession_uuid', $search_params['profession_id']);
            });
        }

        if (isset($search_params['gender']) && !empty($search_params['gender'])) {
            $query = $query->where('gender', '=', $search_params['gender']);
        }

        if (isset($search_params['businesses']) && !empty($search_params['businesses']) && $search_params['businesses'] == 'true') {
            $query = $query->where('is_business', '=', 1);
        }

        if (isset($search_params['freelancers']) && !empty($search_params['freelancers']) && $search_params['freelancers'] == 'true') {
            $query = $query->where('is_business', '=', 0);
        }


        //        if (isset($search_params['subscription_ids']) && !empty($search_params['subscription_ids'])) {
        //            $query = $query->whereIn('freelancer_uuid', $search_params['subscription_ids']);
        //        }
        //        if (isset($search_params['is_with_subscription']) && !empty($search_params['is_with_subscription'])) {
        //            $query = $query->whereHas('subscriptionFreelancer');
        //        }
        if (isset($search_params['bookings']) && !empty($search_params['bookings']) && $search_params['bookings'] == 'true') {
            $query = $query->where(function ($qb) use ($search_params) {
                $qb->where('profile_type', 1);
                $qb->orWhere('profile_type', 3);
            });

            $query = $query->whereHas('FreelancerCategories', function ($q) use ($search_params) {
                if (isset($search_params['provides_face_to_face']) && !empty($search_params['provides_face_to_face']) && $search_params['provides_face_to_face'] == 'true') {
                    $q->where('is_online', 0);
                }
                if (isset($search_params['provides_online']) && !empty($search_params['provides_online']) && $search_params['provides_online'] == 'true') {
                    $q->where('is_online', 1);
                }
            });
        }

        if (isset($search_params['subscriptions']) && !empty($search_params['subscriptions']) && $search_params['subscriptions'] == true) {
            //            $query = $query->whereHas('subscriptions', function($q) use ($search_params) {
            //                $q->where('subscriber_uuid', $search_params['loggeddy_in_uuid']);
            //            });
            $query = $query->where('receive_subscription_request', '=', 1);
            $query = $query->where('has_subscription', '=', 1);
            $query = $query->where('has_subscription_content', '=', 1);
            //$query->with(['subscriptions' => function ($q) use ($search_params) {
            $query = $query->where(function ($qp) use ($search_params) {
                $qp->where('profile_type', 2);
                $qp->orWhere('profile_type', 3);
            });
            // $query = $query->whereHas('SinglePost', function ($q) {
            //     $q->where('folder_id', '!=', null);
            // }); has_subscription_content check is added thats why this single post check is commented
            //}]);
        }

        if (!empty($search_params['city']) || !empty($search_params['country'])) {
            $query = $query->whereHas('locations.location', function ($q) use ($search_params) {
                if (!empty($search_params['country']))
                    $q->where('country', strtolower($search_params['country']));
                if (!empty($search_params['city']))
                    $q->where('city', strtolower($search_params['city']));
            });
            $query = $query->with(['locations.location' => function ($q) use ($search_params) {
                if (!empty($search_params['country']))
                    $q->where('country', strtolower($search_params['country']));
                if (!empty($search_params['city']))
                    $q->where('city', strtolower($search_params['city']));
            }]);
        }


        $query->with(['following' => function ($q) use ($search_params) {

            $q->where('follower_id', $search_params['logged_in_id']);
        }]);

        $query->with(['favourites' => function ($q) use ($search_params) {
            $q->where('customer_id', $search_params['logged_in_id']);
        }]);

        $query->with('FreelancerCategories.category');
        $query->with(['primary_location' => function ($loc_qry) {
            $loc_qry->where(['type' => 'primary', 'is_archive' => 0]);
            $loc_qry->with('location');
        }]);
        $query->withCount('reviews');
        if (isset($search_params['logged_in_uuid']) && !empty($search_params['logged_in_uuid'])) {

            $query->withCount(['likes' => function ($like_qry) use ($search_params) {
                $like_qry->where('user_id', $search_params['logged_in_uuid']);
            }]);
        }

        $query->with("profession");
        $query->with(['reviews' => function ($rev_qry) {
            $rev_qry->selectRaw('avg(rating) as average_rating, reviewed_id')->groupBy('reviewed_id');
        }]);


        if (!isset($search_params['subscriptions']) && empty($search_params['search_text']) && (!empty($search_params['start_radius']) || !empty($search_params['end_radius']))) {
            $haversine = "(6371 * acos(cos(radians(" . $search_params['lat'] . "))
            * cos(radians(`lat`))
            * cos(radians(`lng`)
            - radians(" . $search_params['lng'] . "))
            + sin(radians(" . $search_params['lat'] . "))
            * sin(radians(`lat`))))";
            $query->leftjoin('freelancer_locations','freelancer_locations.freelancer_id','=','freelancers.id')
            ->leftjoin('locations','freelancer_locations.location_id','=','locations.id');
            $query->selectRaw("{$haversine} AS distance")
            ->whereRaw("{$haversine} > " . $search_params['start_radius'])
            ->whereRaw("{$haversine} < " . $search_params['end_radius'])
            ->where('freelancer_locations.type','primary')->where('freelancer_locations.is_archive',0)
            ->orderBy('distance', 'ASC');

            // $query = $query->whereHas('locations', function ($q) use ($search_params) {
            //     $q = self::applyDistanceFilterWithRadiusPoints($q, $search_params);
            // });
        }
        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $result = $query->get();
        //        dd(DB::getQueryLog());exit;
        return !empty($result) ? $result->toArray() : [];
    }

    public static function applyDistanceFilterWithRadiusPoints($query, $search_params)
    {
        $query->whereHas('location', function ($q) use ($search_params) {
            $haversine = "(6371 * acos(cos(radians(" . $search_params['lat'] . "))
                        * cos(radians(`lat`))
                        * cos(radians(`lng`)
                        - radians(" . $search_params['lng'] . "))
                        + sin(radians(" . $search_params['lat'] . "))
                        * sin(radians(`lat`))))";

            return $q->select('*')
                ->selectRaw("{$haversine} AS distance")
                ->whereRaw("{$haversine} > " . $search_params['start_radius'])
                ->whereRaw("{$haversine} < " . $search_params['end_radius'])
                ->orderBy('distance', 'ASC');
        });
    }

    protected function getCustomerFeedStories($column, $value)
    {
        $query = Freelancer::where($column, '=', $value)
            //                ->with('freelancer');
            ->with('ActiveStories.StoryLocation')
            ->with('ActiveStories.ReviewStories')
            ->with('profession')
            ->whereHas('ActiveStories');
        //                ->whereHas('ActiveStories', function($query) {
        //            $query->whereHas('ActiveStories');
        //            ->with('ActiveStories');
        //        });
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    public static function getFreelancerDetailByIds($colum, $value, $subscribe = 'public', $offset = 0, $limit = 20)
    {
        $post_type = ($subscribe == 'public') ? 'unpaid' : 'paid';
        $result = Freelancer::whereIn($colum, $value)
            ->whereHas('posts', function ($q) use ($post_type) {
                $q->where('post_type', $post_type);
            })
            ->with(['posts' => function ($qry) use ($post_type) {
                $qry->where('post_type', $post_type);
            }])
            ->with("profession")->offset($offset)
            ->limit($limit)
            ->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getMultipleProfiles($column, $ids = [], $input_search = [], $offset = null, $limit = null)
    {

        $query = Freelancer::whereIn($column, $ids);
        $query = $query->where('is_archive', '=', 0);
        $query = $query->with('SocialMedia', 'reviews')->with('FreelancerCategories.category');
        $query = $query->with(['subscriptions' => function ($q) use ($input_search) {
            $q->where('subscriber_id', $input_search['profile_id']);
            $q->with('subscription_setting');
        }]);
        $query = $query->with('profession');
        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $result = $query->get();

        return !empty($result) ? $result->toArray() : [];
    }

    //    public static function getFreelancerProfile($column, $value, $offset=0, $limit=20) {
    //         $result = Freelancer::where($column, '=', $value)
    //                ->with('SocialMedia')
    //                //->with('qualifications')
    //                ->with('FreelancerCategories')
    //                //->with('locations.location')
    //                ->with('SubscriptionSettings')
    //                ->offset($offset)
    //                ->limit($limit)
    //                ->get();
    //        return !empty($result) ? $result->toArray() : [];
    //    }

    protected function searchFreelancersPost($search_params = [], $limit = null, $offset = null)
    {
        if (isset($search_params['is_search']) && $search_params['is_search'] == 0) {
            return [];
        }
        if ((isset($search_params['profile_ids']) && empty($search_params['profile_ids'])) && ((isset($search_params['followings']) && $search_params['followings'] == true) || (isset($search_params['favourites']) && $search_params['favourites'] == true) || (isset($search_params['subscriptions']) && $search_params['subscriptions'] == true))) {
            return [];
        }
        $result = [];
        $query = null;
        //        $query = Freelancer::where('is_archive', '=', 0);
        if (isset($search_params['category']) && !empty($search_params['category'])) {
            if (empty($query)) {
                $query = Freelancer::where('is_archive', '=', 0)->whereHas('FreelancerCategories', function ($q) use ($search_params) {
                    $q->whereIn('sub_category_uuid', $search_params['category']);
                });
            } else {
                $query = $query->whereHas('FreelancerCategories', function ($q) use ($search_params) {
                    $q->whereIn('sub_category_uuid', $search_params['category']);
                });
            }
        }
        ////        if (isset($search_params['followings']) && !empty($search_params['followings']) && $search_params['followings'] == true && !empty($search_params['followings_uuids'])) {
        //        if (isset($search_params['followings']) && !empty($search_params['followings']) && $search_params['followings'] == true) {
        //            if (empty($query)) {
        //                $query = Freelancer::where('is_archive', '=', 0)->whereIn('freelancer_uuid', $search_params['followings_uuids']);
        //            } else {
        //                $query = $query->whereIn('freelancer_uuid', $search_params['followings_uuids']);
        //            }
        ////        } elseif (isset($search_params['favourites']) && !empty($search_params['favourites']) && $search_params['favourites'] == true && !empty($search_params['favourites_uuids'])) {
        //        } elseif (isset($search_params['favourites']) && !empty($search_params['favourites']) && $search_params['favourites'] == true) {
        //            if (empty($query)) {
        //                $query = Freelancer::where('is_archive', '=', 0)->whereIn('freelancer_uuid', $search_params['favourites_uuids']);
        //            } else {
        //                $query = $query->whereIn('freelancer_uuid', $search_params['favourites_uuids']);
        //            }
        ////        } elseif (isset($search_params['subscriptions']) && !empty($search_params['subscriptions']) && $search_params['subscriptions'] == true && !empty($search_params['subscriptions_uuids'])) {
        //        } elseif (isset($search_params['subscriptions']) && !empty($search_params['subscriptions']) && $search_params['subscriptions'] == true) {
        //            if (empty($query)) {
        //                $query = Freelancer::where('is_archive', '=', 0)->whereIn('freelancer_uuid', $search_params['subscriptions_uuids']);
        //            } else {
        //                $query = $query->whereIn('freelancer_uuid', $search_params['subscriptions_uuids']);
        //            }
        //        }
        if (isset($search_params['profile_ids']) && !empty($search_params['profile_ids'])) {
            if (empty($query)) {
                $query = Freelancer::where('is_archive', '=', 0)->whereIn('id', $search_params['profile_ids']);
            } else {
                $query = $query->whereIn('id', $search_params['profile_ids']);
            }
        }
        if (array_key_exists('professions', $search_params) && isset($search_params['professions']) && !empty($search_params['professions'])) {
            if (empty($query)) {
                $query = Freelancer::where('is_archive', '=', 0)->whereHas('profession', function ($q) use ($search_params) {
                    $q->whereIn('profession_uuid', $search_params['professions']);
                });
            } else {
                $query = $query->whereHas('profession', function ($q) use ($search_params) {
                    $q->whereIn('profession_uuid', $search_params['professions']);
                });
            }
        }
        if (isset($search_params['businesses']) && !empty($search_params['businesses']) && $search_params['businesses'] == true) {
            if (empty($query)) {
                $query = Freelancer::where('is_archive', '=', 0)->where('is_business', '=', 1);
            } else {
                $query = $query->where('is_business', '=', 1);
            }
        } elseif (isset($search_params['freelancers']) && !empty($search_params['freelancers']) && $search_params['freelancers'] == true) {
            if (empty($query)) {
                $query = Freelancer::where('is_archive', '=', 0)->where('is_business', '=', 0);
            } else {
                $query = $query->where('is_business', '=', 0);
            }
        }
        if (!empty($search_params['city']) || !empty($search_params['country'])) {
            if (empty($query)) {
                $query = Freelancer::where('is_archive', '=', 0)->whereHas('locations.location', function ($q) use ($search_params) {
                    if (!empty($search_params['country']))
                        $q->where('country', strtolower($search_params['country']));
                    if (!empty($search_params['city']))
                        $q->where('city', strtolower($search_params['city']));
                });
            } else {
                $query = $query->whereHas('locations.location', function ($q) use ($search_params) {
                    if (!empty($search_params['country']))
                        $q->where('country', strtolower($search_params['country']));
                    if (!empty($search_params['city']))
                        $q->where('city', strtolower($search_params['city']));
                });
            }
        }
        if (isset($search_params['radius']) && !empty($search_params['radius'])) {
            $search_params['start_radius'] = 0;
            $search_params['end_radius'] = $search_params['radius'];
            if (empty($query)) {
                $query = Freelancer::where('is_archive', '=', 0)->whereHas('locations', function ($q) use ($search_params) {
                    $q = self::applyDistanceFilterWithRadiusPoints($q, $search_params);
                    //                $q = self::applyDistanceFilter($q, $search_params);
                });
            } else {
                $query = $query->whereHas('locations', function ($q) use ($search_params) {
                    $q = self::applyDistanceFilterWithRadiusPoints($q, $search_params);
                    //                $q = self::applyDistanceFilter($q, $search_params);
                });
            }
        }
        if (!empty($query)) {
            $query->with('FreelancerCategories.category');
            $query->with('profession');
            $query->with(['primary_location' => function ($loc_qry) {
                $loc_qry->where(['type' => 'primary', 'is_archive' => 0]);
                $loc_qry->with('location');
            }]);
            if (!empty($offset)) {
                $query = $query->offset($offset);
            }
            if (!empty($limit)) {
                $query = $query->limit($limit);
            }
            $result = $query->pluck('id');
        }
        return !empty($result) ? $result->toArray() : [];
    }

    public static function applyDistanceFilter($query, $search_params)
    {
        $query->whereHas('location', function ($q) use ($search_params) {
            $haversine = "(6371 * acos(cos(radians(" . $search_params['lat'] . "))
                        * cos(radians(`lat`))
                        * cos(radians(`lng`)
                        - radians(" . $search_params['lng'] . "))
                        + sin(radians(" . $search_params['lat'] . "))
                        * sin(radians(`lat`))))";

            return $q->select('*')
                ->selectRaw("{$haversine} AS distance")
                ->whereRaw("{$haversine} < " . $search_params['radius'])
                ->orderBy('distance', 'ASC');
        });
    }

    // check for chat event
    protected function checkFreelancerExistence($col, $val)
    {
        $result = Freelancer::where($col, $val)->first();
        return !empty($result) ? $result->toArray() : [];
    }

    // new query for search
    public static function searchFreelancersForChat($search_key = "", $ids = [], $limit = 100, $offset = 0)
    {

        $query = Freelancer::whereIn('id', $ids)
            ->where(function ($q) use ($search_key) {
                $q = $q->where(function ($sql) use ($search_key) {
                    $sql = $sql->where('first_name', 'like', "%$search_key%");
                });
                //            $q = $q->where('first_name', 'like', "%$search_key%");
                $q = $q->ORwhere(function ($sql) use ($search_key) {
                    $sql = $sql->where('last_name', 'like', "%$search_key%");
                });
                //            $q = $q->orWhere('last_name', 'like', "%$search_key%");
            });
        $users = self::getSearch($query, $offset, $limit);
        return (!empty($users)) ? $users->toArray() : [];
    }

    // previous query
    //    public static function searchFreelancersForChat($search_key = "", $ids = [], $limit = 100, $offset = 0) {
    ////        $query = Freelancer::where(function($q)use($search_key) {
    ////                    $q = $q->where(DB::raw('concat(first_name," ",last_name)'), 'LIKE', "$search_key%");
    ////                });
    ////        $users = self::getSearch($query, $offset, $limit);
    ////        if ($users->isEmpty()) {
    //        $query = Freelancer::where(function ($q)use ($search_key) {
    //                    $q = $q->where('public_chat', '=', 1)
    //                            ->where(function ($q)use ($search_key) {
    //                        $q = $q->where('first_name', 'like', "%$search_key%");
    //                        $q = $q->orWhere('last_name', 'like', "%$search_key");
    //                    });
    //                })
    //                ->orwhere(function ($q)use ($search_key, $ids) {
    //            $q = $q->whereIn('freelancer_uuid', $ids);
    //            $q = $q->where('first_name', 'like', "%$search_key%");
    //            $q = $q->orWhere('last_name', 'like', "%$search_key");
    //        });
    //        $users = self::getSearch($query, $offset, $limit);
    ////        }
    //        return (!empty($users)) ? $users->toArray() : [];
    //    }

    public static function getSearch($query, $offset, $limit)
    {
        $query = $query->where('is_archive', '=', 0);
        //        $query = $query->where('public_chat', '=', 1);
        $query = $query->limit($limit);
        $query = $query->offset($offset);
        $users = $query->get();

        return !empty($users) ? $users : [];
    }

    protected function updateStatus($column, $value, $data)
    {
        $result = Freelancer::where($column, '=', $value)->update($data);

        return ($result) ? true : false;
    }

    public static function getParticularFreelancer($freelancer_uuid)
    {
        $result = Freelancer::with('profession')->where("id", '=', $freelancer_uuid)->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function checkFreelancerExistByPhone($phn)
    {
        $resp = self::where('phone_number', $phn)->first();
        return $resp;
    }
}
