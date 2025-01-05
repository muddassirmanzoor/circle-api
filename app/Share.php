<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Share extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'shares';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'share_uuid';
    public $timestamps = true;
    protected $fillable = [
        'share_uuid',
        'profile_uuid',
        'content_uuid',
        'sharing_channel',
        'is_archive'
    ];

    protected function saveData($data) {
        $save = Share::create($data);
        return !empty($save) ? $save->toArray() : [];
    }

}
