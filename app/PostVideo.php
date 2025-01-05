<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PostVideo extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'post_videos';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'post_video_uuid';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'post_video_uuid',
        'post_id',
        'folder_id',
        'post_video',
        'video_thumbnail',
        'height',
        'width',
        'duration',
        'is_archive',
        'created_at',
        'updated_at'
    ];

    /*
     * All model relations goes down here
     *
     */

    protected function saveNewPostVideo($data) {
        return PostVideo::create($data);
    }

    protected function saveMultiplePostVideo($data) {
        return PostVideo::insert($data);
    }

    protected function deletePostVideo($column, $value) {
        if (PostVideo::where($column, '=', $value)->exists()) {
            return PostVideo::where($column, '=', $value)->delete();
        }
        return true;
    }

    protected function updatePostVideo($column, $value, $data) {
        if (PostVideo::where($column, '=', $value)->exists()) {
            return PostVideo::where($column, '=', $value)->update($data);
        } else {
            return PostVideo::create($data);
        }
        return PostVideo::where($column, '=', $value)->update($data);
    }

}
