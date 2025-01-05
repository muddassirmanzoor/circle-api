<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FreelancerLocation extends Model {

    protected $table = 'freelancer_locations';
    protected $uuidFieldName = 'freelancer_location_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['freelancer_location_uuid', 'freelancer_id', 'location_id', 'type', 'comments', 'is_gym', 'gym_name', 'gym_logo', 'is_archive', 'created_at', 'updated_at'];

    /*
     * All model relations goes down here
     *
     */

    public function location() {
        return $this->hasOne('\App\Location', 'id', 'location_id');
    }

    public function freelancer() {
        return $this->belongsTo('App\Freelancer', 'freelancer_uuid', 'freelancer_uuid')->where('is_archive', '=', 0);
    }

    protected function saveLocation($data) {
        return FreelancerLocation::create($data);
    }

    protected function insertLocation($data) {
        return FreelancerLocation::insert($data);
    }

    protected function checkLocation($column, $value) {
        return FreelancerLocation::where($column, '=', $value)->exists();
    }

    protected function checkFreelancerLocation($column, $value) {
        $result = FreelancerLocation::where($column, '=', $value)->where('is_archive', '=', 0)->first();
        return $result ? $result->toArray() : [];
    }

    protected function updateLocation($column, $value, $data) {
        return FreelancerLocation::where($column, '=', $value)->update($data);
    }

    protected function getFreelancerLocations($column, $value) {
        $result = FreelancerLocation::where($column, '=', $value)->where('is_archive', '=', 0)
                ->with('location')
                ->get();
        return !empty($result) ? $result->toArray() : [];
    }

    public static function getProfileAddresses($location_uuids = [], $limit = null, $offset = null) {
        $query = FreelancerLocation::whereIn('location_uuid', $location_uuids)
                ->where('is_archive', '=', 0);
        $query = $query->wherehas('freelancer')
                ->with('freelancer');
        $query = $query->limit($limit);
        $query = $query->offset($offset);
        $result = $query->get();
        return (!empty($result)) ? $result->toArray() : [];
    }

}
