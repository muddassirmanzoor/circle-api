<?php

namespace App;

use App\Helpers\CommonHelper;
use Illuminate\Database\Eloquent\Model;

class FreelanceCategory extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'freelancer_categories';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'freelancer_category_uuid';
    public $timestamps = true;
    protected $fillable = [
        'freelancer_category_uuid',
        'freelancer_id',
        'category_id',
        'sub_category_id',
        'name',
        'currency',
        'price',
        'duration',
        'image',
        'description',
        'description_video',
        'description_video_thumbnail',
        'is_online',
        'is_archive'
    ];

    public function category() {
        return $this->hasOne('\App\Category', 'id', 'category_id');
    }

    public function SubCategory() {
        return $this->hasOne('\App\SubCategory', 'id', 'sub_category_id');
    }


    public function Freelancer() {
        return $this->hasOne('\App\Freelancer', 'id', 'freelancer_id');
    }

    protected function create($data) {
        $result = FreelanceCategory::create($data);
        return !empty($result) ? $result->toArray() : [];
    }

    protected function insertCategories($data = []) {
        return FreelanceCategory::insert($data);
    }

    protected function updateCategories($column, $value, $data = []) {
        return FreelanceCategory::where($column,  $value)->update($data);
    }

    protected function updateMultipleCategories($column, $ids_array, $data) {
        $result = FreelanceCategory::whereIn($column, $ids_array)->update($data);
        return $result ? true : false;
    }

    protected function getAllCategories($column, $value) {
        $freelance_categories = FreelanceCategory::where($column, '=', $value)->where('is_archive', '=', 0)
                ->with('category')
                ->with('SubCategory')
                ->with('Freelancer')
                ->get();
        return !empty($freelance_categories) ? $freelance_categories->toArray() : [];
    }

    protected function getAllCategoriesWithoutCondition($column, $value) {
        $freelance_categories = FreelanceCategory::where($column,$value)
                ->where('is_archive', '=', 0)
                ->with('category')
                ->with('SubCategory')
                ->get();
        
        return !empty($freelance_categories) ? $freelance_categories->toArray() : [];
    }

    protected function getCategory($column, $value) {
        $freelance_categories = FreelanceCategory::where($column, '=', $value)->where('is_archive', '=', 0)
                ->with('category')
                ->with('SubCategory')
                ->with('Freelancer')
                ->first();
        return !empty($freelance_categories) ? $freelance_categories->toArray() : [];
    }

    protected function deleteFreelancerCategories($column, $value) {
        return FreelanceCategory::where($column, '=', $value)->delete();
    }

    protected function getFreelancerCategory($column, $value) {
        $freelance_categories = FreelanceCategory::where($column, '=', $value)->where('is_archive', '=', 0)->with('SubCategory', 'category')->first();
        return !empty($freelance_categories) ? $freelance_categories->toArray() : [];
    }

    protected function checkCategoryExistAgaistFreelancer($inputs) {

        $suCatId = CommonHelper::getSubCategoryIdByUuid($inputs['sub_category_uuid']);

        return FreelanceCategory::where(['is_archive' => 0, 'sub_category_id' =>$suCatId,
            'freelancer_id' => $inputs['freelancer_id']])->exists();
    }

}
