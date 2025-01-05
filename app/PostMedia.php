<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PostMedia extends Model
{
    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'post_media';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'media_uuid';
    public $timestamps = true;
    protected $fillable = ['media_uuid', 'post_id','media_type', 'folder_id', 'media_src', 'video_thumbnail', 'height', 'width', 'duration', 'is_archive'];

    protected function saveNewPostMedia($data) {
        return PostMedia::create($data);
    }

    protected function saveMultiplePostMedia($data) {
        return PostMedia::insert($data);
    }

    protected function deletePostMedia($column, $value) {
        if (PostMedia::where($column, '=', $value)->exists()) {
            return PostMedia::where($column, '=', $value)->delete();
        }
        return true;
    }

    protected function updatePostMedia($column, $value, $data) {
        if (PostMedia::where($column, '=', $value)->exists()) {
            return PostMedia::where($column, '=', $value)->update($data);
        } else {
            return PostMedia::create($data);
        }
        return PostMedia::where($column, '=', $value)->update($data);
    }
}
