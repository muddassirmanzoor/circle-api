<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IpAddress extends Model {

    protected $table = 'ip_addresses';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'ip_address',
        'country',
        'city',
        'is_archive'
    ];

    protected function saveIpAddress($data) {
        $result = IpAddress::create($data);
        return !empty($result) ? $result->toArray() : [];
    }

    protected function checkIpAddress($col, $val) {
        $result = IpAddress::where($col, '=', $val)
                ->where('is_archive', '=', 0)
                ->first();
        return !empty($result) ? $result->toArray() : [];
    }

}
