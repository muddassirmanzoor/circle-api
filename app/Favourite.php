<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Favourite extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'favourites';
    protected $uuidFieldName = 'favourite_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['favourite_uuid', 'customer_id', 'freelancer_id', 'is_archive', 'created_at', 'updated_at'];

    /*
     * All model relations goes down here
     *
     */

    protected function checkFavourite($freelancer_id, $customer_id) {
        return Favourite::where('freelancer_id', '=', $freelancer_id)->where('customer_id', '=', $customer_id)->exists();
    }

    protected function addFavourite($data) {
        $result = Favourite::create($data);
        return (!empty($result)) ? $result->toArray() : [];
    }

    protected function checkAndRemove($freelancer_id, $customer_id) {
        if (Favourite::where('freelancer_id', '=', $freelancer_id)->where('customer_id', '=', $customer_id)->exists()) {
            return Favourite::where('freelancer_id', '=', $freelancer_id)->where('customer_id', '=', $customer_id)->delete();
        }
        return true;
    }

    public static function getFavouriteProfileIds($column, $value, $pluck_field) {
        $result = Favourite::where($column, '=', $value)->pluck($pluck_field);
        return !empty($result) ? $result->toArray() : [];
    }

}
