<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchasedPremiumFolder extends Model
{

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'purchased_premium_folders_uuid';
    protected $fillable = ['purchased_premium_folders_uuid','customer_id','freelancer_id','folder_id','folder_price','amount_paid','currency','status','payment_status'];


    protected  function getPurchasedPremiumFolder($col,$val)
    {
        return $this->where($col,$val)->with('purchase.purchasesTransition')->first();
    }
    public function folder()
    {
        return $this->belongsTo('\App\Folder', 'folder_id', 'id')->where('is_archive', 0);
    }
    public function purchase()
    {
        return $this->belongsTo('\App\Purchases', 'id', 'purchased_premium_folders_id')->where('is_archive', 0);
    }

    protected function getPassedPremiumFolders() {
        $date = date('Y-m-d H:i:s');
        $query = $this->where('is_archive', '=', 0)->where('created_at', '<', $date);
   
        $query = $query->whereHas('purchase', function ($q) {
            $q->whereNotIn('status', ['completed']);
        });
        $query = $query->with('subscription_setting');
        $query = $query->with('purchase');

        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }
}