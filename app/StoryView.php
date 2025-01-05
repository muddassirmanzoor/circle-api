<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StoryView extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'story_views';
    protected $uuidFieldName = 'story_views_uuid';
    protected $primaryKey = 'id';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['story_id', 'user_id', 'is_archive'];

    /*
     * All model relations goes down here
     *
     */

    public function freelancer() {
        return $this->belongsTo('App\Freelancer', 'profile_uuid', 'freelancer_uuid');
    }

    protected function saveStoryView($data = []) {
        $save = StoryView::insert($data);
        return $save;
    }

    protected function pluckData($column, $value, $pluck_column = 'id') {
        $result = self::where($column, '=', $value)->pluck($pluck_column);
        return !empty($result) ? $result->toArray() : [];
    }


}
