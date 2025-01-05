<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Folder extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'folders';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'folder_uuid';
    public $timestamps = true;
    protected $fillable = [
        'folder_uuid',
        'freelancer_id',
        'name',
        'image',
        'type',
        'is_archive',
        'folder_price',
        'is_premium',
        'currency'
    ];

    public function posts() {
        return $this->hasMany('App\Post', 'folder_id', 'id')->where('is_archive', '=', 0);
    }

    public function SinglePost() {
        return $this->hasOne('App\Post', 'folder_id', 'id')->where('is_archive', '=', 0);
    }

    protected function saveFolder($data) {
        $save = Folder::create($data);
        return !empty($save) ? $save->toArray() : [];
    }

    protected function getFolders($col, $val) {
        $result = Folder::where($col, '=', $val)
                ->where('is_archive', '=', 0)
                ->with('SinglePost')
                ->get();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getFolder($col, $val) {
        $result = Folder::with('purchasedPremiumFolder')->where($col, '=', $val)->where('is_archive', '=', 0)->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function updateFolder($col, $val, $data) {
        return Folder::where($col, '=', $val)->update($data);
    }

    protected function getFolderId($folder_uuid)
    {
        return $this->where('folder_uuid',$folder_uuid)->value('id');
    }

    public function purchasedPremiumFolder()
    {
        return $this->belongsTo('App\PurchasedPremiumFolder','id','folder_id');
    }

}
