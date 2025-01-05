<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use DB;

class Customer extends Authenticatable {

    use Notifiable;

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'customers';
    protected $guard = 'customer';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'customer_uuid';
    public $timestamps = true;
    protected $fillable = [
        'id',
        'customer_uuid',
        'freelancer_id',
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'country_code',
        'country_name',
        'password',
        'profile_image',
        'cover_image',
        'dob',
        'age',
        'gender',
        'type',
        'facebook_id',
        'google_id',
        'apple_id',
        'address',
        'address_comments',
        'lat',
        'lng',
        'default_currency',
        'onboard_count',
        'is_verified',
        'public_chat',
        'is_active',
        'is_login',
        'is_archive'
    ];

    public function AppointmentClient() {
        return $this->hasMany('\App\Appointment', 'customer_id', 'id');
    }

    public function token() {

        return $this->hasOne('\App\CustomerCard', 'customer_id', 'id');
    }

    public function AppointmentReview() {
        return $this->hasOne('\App\Appointment', 'customer_id', 'id');
    }

    public function posts() {
        return $this->hasMany('\App\Post', 'profile_uuid', 'customer_uuid');
    }

    public function interests() {
        return $this->hasMany('\App\Interest', 'customer_id', 'id');
    }

    protected function saveCustomer($data = []) {
        $save = Customer::create($data);
        return !empty($save) ? $save->toArray() : [];
    }

    protected function getAllCustomers() {
        $customers = self::orderBy('id', 'desc')->get();
        return !empty($customers) ? $customers->toArray() : [];
    }

    protected function pluckCustomerAttribute($column, $value, $pluck_column = 'id') {
        $result = Customer::where($column, '=', $value)->pluck($pluck_column);
        return !empty($result) ? $result->toArray() : [];
    }

    protected function searchCustomer($search_key = "", $limit = 100) {
        $query = Customer::where('is_archive', '=', 0);
        $query = $query->where(function ($q)use ($search_key) {
            $q = $q->where('first_name', 'like', "%$search_key%");
            $q = $q->orWhere('last_name', 'like', "%$search_key%");
        });
        $query = $query->limit($limit);
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function searchClientCustomers($ids = [], $search_key = "", $limit = 100,$freelancer_id=null) {
        $query = Customer::where('is_archive', '=', 0);
            if($ids !=null){

                $query->whereIn('id', $ids);
            }
            if($freelancer_id !=null){

                $query->where('freelancer_id','=', $freelancer_id);
            }
        $query = $query->where(function ($q)use ($search_key) {

            $q = $q->where('first_name', 'like', "%$search_key%");
            $q = $q->orWhere('last_name', 'like', "%$search_key%");

        });
        $query = $query->with('AppointmentClient');
        $query = $query->limit($limit);
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }



    protected function getSingleCustomer($column, $value) {
        $query = Customer::where($column, '=', $value);
        $query = $query->with(['AppointmentReview' => function ($rev_qry) {
                $rev_qry->whereDoesntHave('review');
                $rev_qry->with('review', 'AppointmentFreelancer');
                $rev_qry->where(['is_archive' => 0])
                        ->where('status', '=', 'confirmed')
                        ->where('appointment_end_date_time', '<=', strtotime(date('Y-m-d H:i:s')));
                        //->where('to_time', '<=', date('H:i:s'));
            }]);
        $query = $query->with('interests.category');
        $query = $query->where('is_archive', 0);
        $result = $query->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function checkCustomer($column, $value) {
        return Customer::where($column, '=', $value)->exists();
    }

    protected function checkCustomerWithDetails($column, $value) {
        $result = Customer::where($column, '=', $value)->where('is_archive', '=', 0)->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function updateCustomer($column, $value, $data) {
        return Customer::where($column, '=', $value)->update($data);
    }

    protected function getCustomerDetail($column, $value) {
        $result = Customer::where($column, '=', $value)->with('interests.category')->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getCustomerDetailByIds($column, $value, $subscribe = 'public', $offset = 0, $limit = 20) {
        $post_type = ($subscribe == 'public') ? 'unpaid' : 'paid';
        $result = Customer::whereIn($column, $value)
                ->whereHas('posts', function ($q) use ($post_type) {
                    $q->where('post_type', $post_type);
                })
                ->with(['posts' => function ($qry) use ($post_type) {
                        $qry->where('post_type', $post_type);
                    }])
                ->offset($offset)
                ->limit($limit)
                ->get();
        ;
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getLikedCustomers($ids = []) {
        $query = Customer::where('is_archive', '=', 0)->whereIn('customer_uuid', $ids);
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getSingleCustomerDetail($column, $value) {
        $result = Customer::where($column, '=', $value)->where('is_archive', '=', 0)->with('interests.category')->first();
        return !empty($result) ? $result->toArray() : [];
    }

//    public static function searchCustomersForChat($search_key = "", $ids = [], $limit = 100, $offset = 0) {
////        $query = Customer::where(function($q)use($search_key) {
////                    $q = $q->where(DB::raw('concat(first_name," ",last_name)'), 'LIKE', "$search_key%");
////                });
////        $users = self::getSearch($query, $offset, $limit);
////        if ($users->isEmpty()) {
//        $query = Customer::where(function($q)use($search_key) {
//                    $q = $q->where('public_chat', '=', 1);
//                        $q = $q->where('first_name', 'like', "%$search_key%");
//                        $q = $q->orWhere('last_name', 'like', "%$search_key%");
//                })
//                ->orwhere(function($q)use($search_key, $ids) {
//            $q = $q->whereIn('customer_uuid', $ids);
//                $q = $q->where('first_name', 'like', "%$search_key%");
//                $q = $q->orWhere('last_name', 'like', "%$search_key%");
//            });
////                ->where(function($q) {
////            $q = $q->where('public_chat', '=', 1);
////        });
//        $users = self::getSearch($query, $offset, $limit);
////        }
//        return (!empty($users)) ? $users->toArray() : [];
//    }
    //new chat search query
    public static function searchCustomersForChat($search_key = "", $ids = [], $limit = 100, $offset = 0) {
        $query = Customer::whereIn('id', $ids)
                ->where(function ($q) use ($search_key) {
            $q = $q->where(function ($sql) use ($search_key) {
                $sql = $sql->where('first_name', 'like', "%$search_key%");
            });
            $q = $q->ORWhere(function ($sql) use ($search_key) {
                $sql = $sql->where('last_name', 'like', "%$search_key%");
            });
        });
        $users = self::getSearch($query, $offset, $limit);
        return (!empty($users)) ? $users->toArray() : [];
    }

    //previous chat search query
//    public static function searchCustomersForChat($search_key = "", $ids = [], $limit = 100, $offset = 0) {
////        $query = Customer::where(function($q)use($search_key) {
////                    $q = $q->where(DB::raw('concat(first_name," ",last_name)'), 'LIKE', "$search_key%");
////                });
////        $users = self::getSearch($query, $offset, $limit);
////        if ($users->isEmpty()) {
//        $query = Customer::where(function ($q)use ($search_key) {
//                    $q = $q->where('public_chat', '=', 1)
//                            ->where(function ($q) use ($search_key) {
//                        $q = $q->where('first_name', 'like', "%$search_key%");
//                        $q = $q->orWhere('last_name', 'like', "%$search_key");
//                    });
//                })
//                ->orwhere(function ($q)use ($search_key, $ids) {
//            $q = $q->whereIn('customer_uuid', $ids)
//                    ->where(function ($q) use ($search_key) {
//                $q = $q->where('first_name', 'like', "%$search_key%");
//                $q = $q->orWhere('last_name', 'like', "%$search_key");
//            });
////            $q = $q->where('public_chat', '=', 1);
////            $q = $q->where('first_name', 'like', "%$search_key%");
////            $q = $q->orWhere('last_name', 'like', "%$search_key%");
//        });
////                ->where(function($q) {
////            $q = $q->where('public_chat', '=', 1);
////        });
//        $users = self::getSearch($query, $offset, $limit);
////        }
//        return (!empty($users)) ? $users->toArray() : [];
//    }

    public static function getSearch($query, $offset, $limit) {
        $query = $query->where('is_archive', '=', 0);
//        $query = $query->where('public_chat', '=', 1);
        $query = $query->limit($limit);
        $query = $query->offset($offset);
        $users = $query->get();

        return !empty($users) ? $users : [];
    }

    protected function updateStatus($column, $value, $data) {
        $result = Customer::where($column, '=', $value)->update($data);
        return ($result) ? true : false;
    }

    protected function getAdminDetail($column, $value) {
        $result = Customer::where($column, '=', $value)->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getSupportDetails($column, $value) {
        $result = Customer::where($column, '=', $value)
                            ->where('email', 'support@circlonline.com')
                            ->first();

        if(empty($result)) {
            $result = Customer::where($column, '=', $value)->first();
        }

        return !empty($result) ? $result->toArray() : [];
    }


    public static function getParticularCustomer($customer_uuid) {
        $result = Customer::where("id", '=', $customer_uuid)->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function checkCustomerExistByPhone($phn) {
        $resp = self::where('phone_number', $phn)->first();
        return $resp;
    }

}
