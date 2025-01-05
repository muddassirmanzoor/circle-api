<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FreelancerNewCategory extends Model {

    protected $table = 'freelancer_created_sub_categories';
    protected $uuidFieldName = 'freelancer_created_sub_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['freelancer_created_sub_uuid', 'freelancer_id', 'name', 'image', 'is_archive', 'created_at', 'updated_at'];

    /*
     * All model relations goes down here
     *
     */

    protected function saveCategory($data) {
        $result = FreelancerNewCategory::create($data);
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getCustomCategories($column, $value) {
        $result = FreelancerNewCategory::where($column, '=', $value)->get();
        return !empty($result) ? $result->toArray() : [];
    }

}
