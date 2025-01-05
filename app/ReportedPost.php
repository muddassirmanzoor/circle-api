<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReportedPost extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'reported_posts';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'reported_post_uuid';
    public $timestamps = true;
    protected $fillable = [
        'reported_post_uuid',
        'post_id',
        'reporter_id',
        'reported_type',
        'comments',
        'is_archive'
    ];

    protected function addReportPost($data) {
        $result = ReportedPost::create($data);
        return !empty($result) ? $result->toArray() : [];
    }

}
