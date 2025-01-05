<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'sub_categories';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'sub_category_uuid';
    public $timestamps = true;
    protected $fillable = [
        'sub_category_uuid',
        'category_id',
        'freelancer_id',
        'name',
        'image',
        'is_online',
        'is_archive',
    ];

    protected function getSubCategories($where = '') {
        $sub_categories = array();
        if (!empty($where)) {
            $sub_categories = SubCategory::where($where)->where('freelancer_id', '=', null)->where('is_archive', '=', 0)->orderBy('id', 'asc')->get();
        } else {
            $sub_categories = SubCategory::where('is_archive', '=', 0)->where('freelancer_id', '=', null)
                            ->orderBy('sub_categories.id', 'asc')->get();
        }
        return !empty($sub_categories) ? $sub_categories : '';
    }

    protected function getSubCategorydataById($id) {
        $data = array();
        $data = self::where('sub_category_uuid', '=', $id)->first();
        return !empty($data) ? $data : '';
    }

    protected function updateSubCategorydataById($data, $id) {
        self::where('sub_category_uuid', '=', $id)->update($data);
    }

    public static function checkNewSubCategory($param) {
        $result = self::where('freelancer_id', $param['freelancer_id'])->where('category_id', $param['category_id'])->where('name', $param['name'])->first();
        return ($result) ? true : false;
    }

}
