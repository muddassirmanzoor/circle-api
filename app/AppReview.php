<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AppReview extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'app_reviews';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'app_review_uuid';
    public $timestamps = true;
    protected $fillable = [
        'app_review_uuid',
        'user_id',
        'type',
        'comments',
        'is_archive'
    ];

    protected function saveFeedBack($data) {
        $save = AppReview::create($data);
        return !empty($save) ? $save->toArray() : [];
    }

}
