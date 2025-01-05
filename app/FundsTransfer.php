<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
class FundsTransfer extends Model
{
    //
    use \BinaryCabin\LaravelUUID\Traits\HasUUID;
    protected $uuidFieldName = 'funds_transfer_uuid';

    protected $guarded = ['id'];

    protected $fillable =  [
    'reference_no',
    'connect_id',
    'file_customer_id',
    'file_authorization_type',
    'total_number_of_payments',
    'status',
    'is_transfered',
];

    public function freelancer_earning()
    {
        return $this->hasOne('App\FreelancerEarning','funds_transfers_id','id');
    }
    public function freelancer_earnings()
    {
        return $this->hasMany('App\FreelancerEarning','funds_transfers_id','id');
    }
    public static function getSingleFreelancerPayouts($col,$val,$limit = null,$offset = null,$freelancer_id = null)
    {
        # code...
        $query = self::with('freelancer_earning.freelancer.BankDetail','freelancer_earning.freelancerWithdrawl');
        $query->with(['freelancer_earnings'=>function($q) use($freelancer_id,$val){
            $q->where('freelancer_id',$freelancer_id);
        }]);
        $query->whereHas('freelancer_earnings',function($q) use($freelancer_id,$val){
            $q->where('freelancer_id',$freelancer_id)->where('transfer_status',$val);
        });
        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $query = $query->get();
        return ($query) ? $query : [];
    }

    public static function getPayout($col,$val,$freelancer_id = null)
    {
        $query = self::where($col,$val)->with('freelancer_earning.freelancer.BankDetail')
        ->with(['freelancer_earnings'=>function($q) use($freelancer_id){
            $q->where('freelancer_id',$freelancer_id);
        }])
        ->whereHas('freelancer_earnings',function($q) use($freelancer_id){
            $q->where('freelancer_id',$freelancer_id);
        })->first();
        return ($query) ? $query : [];
    }
    public static function getPayoutDetail($col,$val,$limit = null,$offset = null)
    {
        # code...
        $query = self::where($col,$val)
        ->with('freelancer_earnings');
        // if (!empty($offset)) {
        //     $query = $query->offset($offset);
        // }
        // if (!empty($limit)) {
        //     $query = $query->limit($limit);
        // }
        $query = $query->first();
        return ($query) ? $query->toArray() : [];
    }
}
