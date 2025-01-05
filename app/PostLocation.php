<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;

class PostLocation extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'post_locations';
    protected $uuidFieldName = 'post_location_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['post_location_uuid', 'post_id', 'location_id', 'address', 'route', 'street_number', 'city', 'state', 'country', 'country_code', 'zip_code', 'place_id', 'lat', 'lng', 'added_by', 'is_archive', 'created_at', 'updated_at'];

    /*
     * All model relations goes down here
     *
     */

    protected function saveLocation($data) {
        return PostLocation::create($data);
    }

    protected function checkLocation($column, $value) {
        return PostLocation::where($column, '=', $value)->exists();
    }

    protected function updateLocation($column, $value, $data) {
        return PostLocation::where($column, '=', $value)->update($data);
    }

}
