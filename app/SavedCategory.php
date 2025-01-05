<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SavedCategory extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'saved_category';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'saved_category_uuid';
    public $timestamps = true;
    protected $fillable = [
        'saved_category_uuid',
        'user_id',
        'category_id',
        'is_archive'
    ];

    public function category() {
        return $this->hasOne('\App\Category', 'id', 'category_id');
    }

    public function Freelancer() {
        return $this->hasOne('\App\Freelancer', 'id', 'user_id');
    }

    protected function createCategory($data) {

        $result = SavedCategory::create($data);
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getProfileCategory($column, $value) {
        $result = SavedCategory::where($column, '=', $value)->with('category')->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function checkSavedCategory($profile_uuid, $category_uuid) {
        $result = SavedCategory::where('profile_uuid', '=', $profile_uuid)
                ->where('category_uuid', '=', $category_uuid)
                ->where('is_archive', '=', 0)
                ->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function checkFreelancerCategory($profile_uuid) {
        $result = SavedCategory::where('user_id', '=', $profile_uuid)
                ->where('is_archive', '=', 0)
                ->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function updateSavedCategory($col, $val, $data) {
        $result = SavedCategory::where($col, '=', $val)
                ->update($data);
        return $result ? true : false;
    }

}
