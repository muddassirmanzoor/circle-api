<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReviewReply extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'review_replies';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'reply_uuid';
    public $timestamps = true;
    protected $fillable = [
        'reply_uuid',
        'review_id',
        'profile_id',
        'reply',
        'is_archive'
    ];

    public function customer() {
        return $this->hasOne('\App\Customer', 'id', 'profile_id');
    }

    public function freelancer() {
        return $this->hasOne('\App\Freelancer', 'id', 'profile_id');
    }

    protected function saveReply($data) {
        $result = ReviewReply::create($data);
        return ($result) ? $result->toArray() : [];
    }

    protected function getSingleReply($column, $value) {
        $result = ReviewReply::where($column, '=', $value)
                ->with('freelancer')
                ->first();
        return !empty($result) ? $result->toArray() : [];
    }

}
