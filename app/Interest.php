<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

//use DB;

class Interest extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'interests';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'interest_uuid';
    public $timestamps = true;
    protected $fillable = [
        'interest_uuid',
        'customer_id',
        'category_id',
        'is_archive'
    ];

    public function category(){
        return $this->hasOne('\App\Category', 'id', 'category_id');
    }

    protected function saveInterests($data) {
        $result = Interest::create($data);
        return !empty($result) ? $result->toArray() : [];
        //return Interest::insert($data);
    }

    protected function saveMultipleInterests($data) {
        return Interest::insert($data);
    }

    protected function getCustomerInterest($column, $value) {
        return Interest::where($column, '=', $value)->first();
    }

}
