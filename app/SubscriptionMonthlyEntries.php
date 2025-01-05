<?php

namespace App;

use App\Helpers\UuidHelper;
use Illuminate\Database\Eloquent\Model;

class SubscriptionMonthlyEntries extends Model
{
    protected $table = 'subscription_entries_by_monthly';

    protected $primaryKey = 'id';
    protected $uuidFieldName = 'subscribed_monthly_uuid';
    public $timestamps = true;
    protected $fillable = [
        'subscribed_monthly_uuid',
        'subscription_id',
        'type',
        'amount',
        'customer_id',
        'freelancer_id',
        'start_date',
        'end_date',
        'is_active',
    ];
    public static function createRecords($records){
        $insertedRecords = SubscriptionMonthlyEntries::insert($records);
        return ($insertedRecords)?true:false;
    }
    public static function getMonthlyRecords($records){
       $records = SubscriptionMonthlyEntries::where('subscription_id',$records['id'])
         ->where('type','pending')
         ->where('is_active',0);
        return ($records)?$records->sum('amount'):null;
    }

}
