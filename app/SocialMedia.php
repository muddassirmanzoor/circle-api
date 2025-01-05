<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SocialMedia extends Model {

    // use Notifiable;

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'social_medias';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'social_media_uuid';
    public $timestamps = true;
    protected $fillable = [
        'social_media_uuid',
        'user_id',
        'social_media_link',
        'social_media_type',
        'is_archive'
    ];

    public static function saveSocialMedia($inputs) {
        return SocialMedia::insert($inputs);
    }

    public static function deleteSocialMedia($column, $value) {
        return SocialMedia::where($column, '=', $value)->delete();
    }

}
