<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'categories';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'category_uuid';
    public $timestamps = true;
    protected $fillable = [
        'category_uuid',
        'name',
        'image',
        'is_archive'
    ];

    public function subCategory() {
        return $this->hasMany('App\SubCategory', 'category_id')->where('is_archive', '=', 0);
    }

    protected function getAllCategories() {
        $categories = Category::where('is_archive',0)
                        ->whereHas('subCategory', function ($query) {
                            $query->where('is_archive', '=', 0);
                        })->get();
        return !empty($categories) ? $categories->toArray() : [];
    }

    protected function getCategorydataById($id) {
        $data = self::where('category_uuid', '=', $id)->first();
        return !empty($data) ? $data->toArray() : [];
    }

    protected function updateCategorydataById($data, $id) {
        self::where('category_uuid', '=', $id)->update($data);
    }

}
