<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Profession extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'professions';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'profession_uuid';
    public $timestamps = true;
    protected $fillable = [
        'profession_uuid',
        'name',
        'is_archive'
    ];

    protected function getAllProfessions() {
        $query = Profession::where('is_archive', '=', 0);
        $result = $query->get();
        return (!empty($result)) ? $result->toArray() : [];
    }
    
    protected function getProfessionName($column , $value) {
        $query = Profession::where($column, '=', $value);
        $result = $query->get();
        return (!empty($result)) ? $result->toArray() : [];
    }
    

}
