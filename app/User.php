<?php

namespace App;

use App\Helpers\UuidHelper;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable {

    use Notifiable;
    use \BinaryCabin\LaravelUUID\Traits\HasUUID;
    protected $table = 'users';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function userFreelancer() {
        return $this->hasOne('App\Freelancer', 'user_id' );
    }

    public function userCustomer() {
        return $this->hasOne('App\Customer', 'user_id' );
    }

    public static function getUserChild($userType,$userId){
            $user = '';

            $freelancer = Freelancer::where('user_id',$userId)->exists();

            if($freelancer){
              return Freelancer::where('user_id',$userId)->first()->freelancer_uuid;
            }else{

                return Customer::where('user_id',$userId)->first()->customer_uuid;
            }


    }

    public static function getUserChildren($userType,$userId){
        if($userType == 'freelancer'){
            return Freelancer::where('user_id',$userId)->value('freelancer_uuid');
        }else{
            return Customer::where('user_id',$userId)->value('customer_uuid');
        }
    }
    public static function saveUser(){

       $user = User::create(['uuid'=>UuidHelper::generateUniqueUUID("users", "user_uuid")]);

       return !empty($user) ? $user->toArray() : [];
    }

}
