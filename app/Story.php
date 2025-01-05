<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Story extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'stories';
    protected $uuidFieldName = 'story_uuid';
    protected $primaryKey = 'id';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['story_uuid', 'freelancer_id', 'text', 'url', 'story_image', 'story_video', 'video_thumbnail', 'is_active', 'is_archive'];

    /*
     * All model relations goes down here
     *
     */

    public function freelancer() {
        return $this->belongsTo('App\Freelancer', 'profile_uuid', 'freelancer_uuid');
    }

    public function StoryLocation() {
        return $this->hasOne('App\StoryLocation', 'story_id', 'id');
    }

    public function ReviewStories() {
        return $this->hasOne('\App\StoryView', 'story_id', 'id');
    }

    protected function saveStory($data = []) {
        $save = Story::create($data);

        return !empty($save) ? $save->toArray() : [];
    }

    protected function saveMultipleStories($data = []) {
        return Story::insert($data);
    }

    protected function getProfileStories($column, $value) {
        $result = Story::where($column, '=', $value)
                ->where('is_active', '=', 1)
                ->whereBetween('created_at', [now()->subMinutes(1440), now()])
                ->with('freelancer')
                ->with('StoryLocation')
                ->orderBy('created_at', 'DESC')
                ->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getActiveStories($column, $value) {
        $result = Story::where($column, '=', $value)
                ->where('is_active', '=', 1)
                ->where('is_archive', '=', 0)
                ->whereBetween('created_at', [now()->subDays(30), now()])
                ->with('StoryLocation')
                ->orderBy('created_at', 'DESC')
                ->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getStoriesWithIds($column, $ids) {
        $result = Story::whereIn($column, $ids)
                ->where('is_active', '=', 1)
                ->whereBetween('created_at', [now()->subMinutes(1440), now()])
                ->orderBy('created_at', 'DESC')
                ->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getAllProfileStories($column, $value, $offset = 0, $limit = 20) {
        $query = Story::where($column, '=', $value)
                ->where('is_archive', '=', 0)
                ->orderBy('created_at', 'DESC');
        $query = $query->offset($offset);
        $query = $query->limit($limit);
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function checkStory($profile_uuid, $story_uuid) {
        $result = Story::where('profile_uuid', '=', $profile_uuid)
                ->where('story_uuid', '=', $story_uuid)
                ->where('is_archive', '=', 0)
                ->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function deleteStory($story_uuid, $profile_uuid) {

        $result = Story::where('story_uuid', '=', $story_uuid)
            ->where('freelancer_id', '=', $profile_uuid)
            ->update(['is_archive' => 1, 'is_active' => 0]);
        return $result ? true : false;
    }

}
