<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StoryLocation extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'story_locations';
    protected $uuidFieldName = 'story_location_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['story_location_uuid	', 'story_id', 'address', 'route', 'street_number', 'city', 'state', 'country', 'country_code', 'zip_code', 'place_id', 'lat', 'lng', 'is_archive', 'created_at', 'updated_at'];

    /*
     * All model relations goes down here
     *
     */

    protected function saveLocation($data) {
        return StoryLocation::create($data);
    }

    protected function insertLocations($data) {
        return StoryLocation::insert($data);
    }

    protected function checkLocation($column, $value) {
        return StoryLocation::where($column, '=', $value)->exists();
    }

    protected function updateLocation($column, $value, $data) {
        return StoryLocation::where($column, '=', $value)->update($data);
    }

}
