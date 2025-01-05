<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FreelanceSubCategory extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'freelancer_sub_categories';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'freelancer_sub_category_uuid';
    public $timestamps = true;
    protected $fillable = [
        'freelancer_sub_category_uuid',
        'freelancer_category_uuid',
        'sub_category_uuid',
        'name',
        'price',
        'duration',
        'is_archive'
    ];

    public function getAllCategories($where = '') {
        $freelance_subcategory = array();
        if (!empty($where)) {
            $freelance_subcategory = FreelanceSubCategory::where($where)->orderBy('id', 'desc')->get();
        } else {
            $freelance_subcategory = FreelanceSubCategory::orderBy('id', 'desc')->get();
        }
        return !empty($freelance_subcategory) ? $freelance_subcategory : '';
    }

}
