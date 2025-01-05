<?php

namespace App;

use App\Helpers\CommonHelper;
use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'promo_codes';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'code_uuid';
    public $timestamps = true;
    protected $fillable = [
        'code_uuid',
        'freelancer_id',
        'coupon_code',
        'valid_from',
        'valid_to',
        'discount_type',
        'coupon_amount',
        'currency',
        'is_archive'
    ];

    protected function savePromoCode($data) {
        $result = PromoCode::create($data);
        return ($result) ? $result : [];
    }

    protected function getPromoCodeDetails($column, $value) {
        $result = PromoCode::where($column, '=', $value)->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getActivePromoCodelist($freelancer_uuid, $limit = null, $offset = null) {
        $freelancer_id = CommonHelper::getFreelancerIdByUuid($freelancer_uuid);
        $query = PromoCode::where('freelancer_id', $freelancer_id)->where('valid_to', '>=', date('Y-m-d'))->where('is_archive', 0);
        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        };
        $result = $query->get();
        return ($result) ? $result->toArray() : [];
    }

    protected function getExpiredPromoCodelist($freelancer_uuid, $limit = null, $offset = null) {
        $freelancer_id = CommonHelper::getFreelancerIdByUuid($freelancer_uuid);
        $query = PromoCode::where('freelancer_id', $freelancer_id)->where('valid_to', '<', date('Y-m-d'))->where('is_archive', 0);
        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        };
        $result = $query->get();
        return ($result) ? $result->toArray() : [];
    }

    protected function validatePromoCodeDetails($freelancer_uuid, $coupon_code) {
        $freelancer_id = CommonHelper::getFreelancerIdByUuid($freelancer_uuid);
        $result = PromoCode::where('freelancer_id', '=', $freelancer_id)->where('coupon_code', '=', $coupon_code)->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function updatePromoCode($col, $val,$data) {
        return PromoCode::where($col, '=', $val)->update($data);
    }

    protected function checkPromoCodeExist($freelancer_uuid, $coupon_code) {
        $freelancer_id = CommonHelper::getFreelancerIdByUuid($freelancer_uuid);
        return  $result = PromoCode::where('freelancer_id', '=', $freelancer_id)->where('coupon_code', '=', $coupon_code)->exists();
    }
}
