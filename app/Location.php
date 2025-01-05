<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;

class Location extends Model {

    protected $table = 'locations';
    protected $uuidFieldName = 'location_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['location_uuid', 'address', 'route', 'street_number', 'city', 'state', 'country', 'country_code', 'zip_code', 'location_id', 'lat', 'lng', 'is_archive', 'created_at', 'updated_at'];

    /*
     * All model relations goes down here
     *
     */

    protected function saveLocation($data) {
        return Location::create($data);

    }

    protected function checkLocation($column, $value) {
        return Location::where($column, '=', $value)->exists();
    }

    protected function updateLocation($column, $value, $data) {
        return Location::where($column, '=', $value)->update($data);
    }

    protected function getLocation($column, $value) {
        $result = Location::where($column, '=', $value)->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function createOrUpdateLocation($data) {
        if (Location::where('location_id', '=', $data['location_id'])->exists()) {
            return Location::where('location_id', '=', $data['location_id'])->update($data);
        } else {
            return Location::create($data);
        }
    }

    public static function getProfileAddress($search_data = null) {
        DB::enableQueryLog();
        $lat = $search_data['lat'];
        $long = $search_data['lng'];
        $result = DB::table("locations")
                        ->select("*"
                                , DB::raw("6371 * acos(cos(radians(" . $lat . "))
        * cos(radians(locations.lat))
        * cos(radians(locations.lng) - radians(" . $long . "))
        + sin(radians(" . $lat . "))
        * sin(radians(locations.lat))) AS distance"))
                        ->groupBy('distance')
                        ->having('distance', '<', 15444440000444400044400003334004)->get();
        return $result;
    }

    protected function getCountries() {
        $result = Location::where('is_archive', '=', 0)->groupBy('country')->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getCities($search) {
        $result = Location::where('is_archive', '=', 0)->where('country', '=', $search)->groupBy('city')->get();
        return !empty($result) ? $result->toArray() : [];
    }


}
